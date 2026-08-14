/**
 * OCR nomor identitas — berjalan SEPENUHNYA di browser karyawan.
 *
 * Foto KTP/KK/NPWP tidak pernah dikirim ke server mana pun: Tesseract dimuat
 * dari aset lokal MIC (public/lib/tesseract) dan diproses di perangkat.
 * Itu sebabnya jalur ini dipilih ketimbang API OCR cloud — memindahkan foto
 * identitas seluruh karyawan ke pihak ketiga adalah keputusan tata kelola
 * data, bukan sekadar pilihan teknis.
 *
 * Hasilnya SELALU berupa usulan yang harus dikonfirmasi manusia. Tesseract
 * kerap keliru pada foto ponsel, dan satu digit salah pada NIK 16 digit
 * membuat nomornya tak berguna untuk BPJS maupun perpajakan.
 */
(function (global) {
    'use strict';

    var BASE   = (global.MIC_BASE_URL || '/').replace(/\/?$/, '/');
    var VENDOR = BASE + 'lib/tesseract/';

    /** Panjang digit yang sah per jenis nomor. */
    var PANJANG = {
        nik_ktp: [16],
        no_kk:   [16],
        no_npwp: [15, 16]
    };

    var workerPromise = null;

    function siapkanWorker(lapor) {
        if (workerPromise) return workerPromise;

        workerPromise = Tesseract.createWorker('eng', 1, {
            workerPath: VENDOR + 'worker.min.js',
            corePath:   VENDOR + 'tesseract-core-simd.wasm.js',
            langPath:   VENDOR,
            gzip:       false,   // traineddata di-vendor tanpa kompresi
            logger:     function (m) {
                if (lapor && m.status === 'recognizing text') {
                    lapor(Math.round((m.progress || 0) * 100));
                }
            }
        }).catch(function (e) {
            workerPromise = null;          // biar percobaan berikutnya tidak ikut gagal
            throw e;
        });

        return workerPromise;
    }

    /**
     * Perkecil foto sebelum dikenali. Foto ponsel bisa 4000px dan membuat
     * Tesseract berjalan puluhan detik tanpa menambah akurasi; 1600px sudah
     * lebih dari cukup untuk deretan angka pada kartu identitas.
     */
    function kecilkan(file, maksLebar) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            var url = URL.createObjectURL(file);

            img.onload = function () {
                URL.revokeObjectURL(url);
                var skala = Math.min(1, maksLebar / img.width);
                var c = document.createElement('canvas');
                c.width  = Math.round(img.width  * skala);
                c.height = Math.round(img.height * skala);

                var ctx = c.getContext('2d');
                ctx.drawImage(img, 0, 0, c.width, c.height);

                // Abu-abu + kontras tinggi: teks kartu identitas jauh lebih
                // terbaca setelah latar berwarna/hologram diratakan.
                var d = ctx.getImageData(0, 0, c.width, c.height);
                var p = d.data;
                for (var i = 0; i < p.length; i += 4) {
                    var abu = 0.299 * p[i] + 0.587 * p[i + 1] + 0.114 * p[i + 2];
                    abu = abu < 110 ? 0 : (abu > 165 ? 255 : (abu - 110) * (255 / 55));
                    p[i] = p[i + 1] = p[i + 2] = abu;
                }
                ctx.putImageData(d, 0, 0);

                c.toBlob(function (b) { b ? resolve(b) : reject(new Error('Gagal memproses gambar.')); }, 'image/png');
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Berkas bukan gambar yang bisa dibaca. Untuk PDF, ketik nomornya manual.'));
            };
            img.src = url;
        });
    }

    /**
     * Cari nomor dengan panjang yang benar di dalam teks hasil OCR.
     * Menoleransi pemisah titik/strip/spasi — NPWP hampir selalu tercetak
     * sebagai 09.254.294.5-017.000, dan OCR mempertahankan pemisah itu.
     */
    function cariNomor(teks, jenis) {
        var sah = PANJANG[jenis] || [16];
        var kandidat = [];

        // Deret angka yang mungkin diselingi pemisah
        var re = /\d[\d.\-\s]{8,32}\d/g, m;
        while ((m = re.exec(teks)) !== null) {
            kandidat.push(m[0].replace(/\D/g, ''));
        }
        // Deret angka murni
        var re2 = /\d{10,20}/g;
        while ((m = re2.exec(teks)) !== null) kandidat.push(m[0]);

        for (var i = 0; i < sah.length; i++) {
            for (var j = 0; j < kandidat.length; j++) {
                if (kandidat[j].length === sah[i]) return kandidat[j];
            }
        }
        return null;
    }

    /* ── PDF ──────────────────────────────────────────────────────────────
       pdf.js dimuat MALAS: hanya karyawan yang benar-benar mengunggah PDF
       yang menanggung 1,3 MB tambahan; yang memakai foto tak terkena apa pun.
    */
    var pdfjsPromise = null;

    function siapkanPdfjs() {
        if (pdfjsPromise) return pdfjsPromise;
        pdfjsPromise = import(BASE + 'lib/pdfjs/pdf.min.js').then(function (mod) {
            mod.GlobalWorkerOptions.workerSrc = BASE + 'lib/pdfjs/pdf.worker.min.js';
            return mod;
        }).catch(function (e) {
            pdfjsPromise = null;
            throw new Error('Gagal memuat pembaca PDF. Ketik nomornya manual.');
        });
        return pdfjsPromise;
    }

    /** Render halaman pertama PDF ke blob PNG, siap dibaca Tesseract. */
    function pdfKeGambar(halaman) {
        var vp = halaman.getViewport({ scale: 2 });   // 2x supaya angka kecil tetap tajam
        var c  = document.createElement('canvas');
        c.width = vp.width; c.height = vp.height;
        return halaman.render({ canvasContext: c.getContext('2d'), viewport: vp }).promise
            .then(function () {
                return new Promise(function (res) { c.toBlob(res, 'image/png'); });
            });
    }

    /**
     * Baca PDF. Lapisan teks dicoba LEBIH DULU: dokumen terbit digital
     * (mis. NPWP dari DJP) menyimpan nomornya sebagai teks sungguhan,
     * sehingga bisa diambil persis tanpa risiko salah baca OCR. Hanya PDF
     * hasil pindai — yang isinya cuma gambar — yang perlu dirender lalu di-OCR.
     */
    function bacaPdf(file, jenis, lapor) {
        return siapkanPdfjs()
            .then(function (pdfjs) {
                return file.arrayBuffer().then(function (buf) {
                    return pdfjs.getDocument({ data: buf }).promise;
                });
            })
            .then(function (doc) {
                return doc.getPage(1);
            })
            .then(function (halaman) {
                return halaman.getTextContent().then(function (tc) {
                    var teks = tc.items.map(function (i) { return i.str; }).join(' ');
                    var nomor = cariNomor(teks, jenis);
                    if (nomor) return { nomor: nomor, teks: teks, sumber: 'teks' };

                    // Tak ada lapisan teks yang berguna → PDF hasil pindai.
                    return pdfKeGambar(halaman)
                        .then(function (blob) { return kecilkan(blob, 1600); })
                        .then(function (kecil) {
                            return siapkanWorker(lapor).then(function (w) { return w.recognize(kecil); });
                        })
                        .then(function (hasil) {
                            var t = (hasil && hasil.data && hasil.data.text) || '';
                            return { nomor: cariNomor(t, jenis), teks: t, sumber: 'ocr' };
                        });
                });
            });
    }

    /**
     * @param {File|Blob} file
     * @param {string}    jenis   nik_ktp | no_kk | no_npwp
     * @param {Function}  lapor   progres 0-100
     * @returns {Promise<{nomor:string|null, teks:string, sumber:string}>}
     *          `sumber` = 'teks' (lapisan teks PDF, akurat) atau 'ocr' (hasil pengenalan).
     */
    function baca(file, jenis, lapor) {
        var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '');
        if (isPdf) return bacaPdf(file, jenis, lapor);

        if (typeof Tesseract === 'undefined') {
            return Promise.reject(new Error('Mesin OCR belum termuat. Muat ulang halaman lalu coba lagi.'));
        }

        return kecilkan(file, 1600)
            .then(function (blob) {
                return siapkanWorker(lapor).then(function (w) { return w.recognize(blob); });
            })
            .then(function (hasil) {
                var teks = (hasil && hasil.data && hasil.data.text) || '';
                return { nomor: cariNomor(teks, jenis), teks: teks, sumber: 'ocr' };
            });
    }

    global.OcrIdentitas = { baca: baca };
})(window);
