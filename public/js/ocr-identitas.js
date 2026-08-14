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

    /**
     * Panjang digit yang sah per jenis nomor.
     *
     * NPWP dipisah dua: `no_npwp` khusus format lama 15 digit, `no_npwp16`
     * khusus format baru. Satu kartu NPWP elektronik memuat KEDUANYA, jadi
     * sekali pindai bisa mengisi dua kolom sekaligus.
     */
    var PANJANG = {
        nik_ktp:   [16],
        no_kk:     [16],
        no_npwp:   [15],
        no_npwp16: [16]
    };

    var workerPromise = null;

    /**
     * Pengenalan ulang KHUSUS ANGKA.
     *
     * Pada foto/pindaian kartu, Tesseract kerap menukar angka dengan huruf
     * yang mirip — NIK 6409022508950001 sempat terbaca "b4Y0O09022508950001",
     * sehingga tak ada deretan 16 digit yang sah dan hasilnya kosong.
     * Membatasi keluaran ke 0-9 menghilangkan kebingungan itu. Tidak dipakai
     * di percobaan pertama karena labelnya ("NIK", "NPWP") ikut hilang, dan
     * label justru yang membedakan nomor KK dari NIK.
     */
    function bacaHanyaAngka(gambar) {
        return Tesseract.createWorker('eng', 1, {
            workerPath: VENDOR + 'worker.min.js',
            corePath:   VENDOR + 'tesseract-core-simd.wasm.js',
            langPath:   VENDOR,
            gzip:       false
        }).then(function (w) {
            return w.setParameters({ tessedit_char_whitelist: '0123456789' })
                .then(function () { return w.recognize(gambar); })
                .then(function (h) {
                    w.terminate();
                    return (h && h.data && h.data.text) || '';
                })
                .catch(function (e) { w.terminate(); throw e; });
        });
    }

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
     * Siapkan gambar untuk dikenali: HANYA memperkecil bila terlalu besar.
     *
     * Sengaja TANPA praproses piksel apa pun. Dua percobaan sebelumnya justru
     * memperburuk hasil pada pindaian KTP sungguhan, dan keduanya terbukti
     * lewat pengujian berdampingan pada berkas yang sama:
     *
     *   asli (warna)      → 6409022508950001   ✓ tepat
     *   + grayscale       → 64090225048950001  ✗ ada digit tersisip
     *   + ambang kontras  → angka lain yang tampak sahih  ✗ paling berbahaya
     *
     * Tesseract melakukan binarisasinya sendiri dan hasilnya lebih baik dari
     * gambar apa adanya. "Membantu" di sini malah membuang informasi.
     *
     * Batas 2400px: cukup tinggi agar deretan angka tetap tajam — pengecilan
     * ke 1600px menurunkan akurasi secara nyata.
     */
    function kecilkan(file, maksLebar) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            var url = URL.createObjectURL(file);

            img.onload = function () {
                URL.revokeObjectURL(url);

                // Sudah cukup kecil → pakai apa adanya, tanpa digambar ulang.
                if (img.width <= maksLebar) { resolve(file); return; }

                var skala = maksLebar / img.width;
                var c = document.createElement('canvas');
                c.width  = Math.round(img.width  * skala);
                c.height = Math.round(img.height * skala);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);

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
     * Pola label per jenis nomor.
     *
     * Panjang saja TIDAK cukup membedakan: pada Kartu Keluarga, NIK dan nomor
     * KK sama-sama 16 digit, dan NIK justru muncul lebih dulu di aliran teks —
     * sehingga pencarian berbasis panjang selalu salah mengambil NIK.
     */
    var LABEL = {
        nik_ktp: [/\bNIK\b[^0-9]{0,25}(\d[\d.\-\s]{14,32})/i],
        no_kk:   [
            /KARTU\s*KELUARGA[^0-9]{0,60}(\d[\d.\-\s]{14,32})/i,
            /\bNo\.?\s*(?:KK|K\.?\s*K\.?)\b[^0-9]{0,15}(\d[\d.\-\s]{14,32})/i
        ],
        // NPWP lama ditulis bertitik (66.924.913.8-721.000) dan TIDAK boleh
        // tertangkap oleh label "NPWP16"; karena itu pola pertama menuntut
        // pemisah titik, dan pola cadangan menolak "NPWP" yang diikuti angka.
        no_npwp:   [
            /\bNPWP\b[^0-9]{0,25}(\d{2}\.\d{3}\.\d{3}\.\d[\-.]\d{3}\.\d{3})/i,
            /\bNPWP(?![0-9])\b[^0-9]{0,25}(\d[\d.\-\s]{13,32})/i
        ],
        no_npwp16: [/\bNPWP\s*[-]?\s*16\b[^0-9]{0,25}(\d[\d.\-\s]{14,32})/i]
    };

    function digit(s) { return String(s).replace(/\D/g, ''); }

    function panjangSah(nomor, jenis) {
        return (PANJANG[jenis] || [16]).indexOf(nomor.length) !== -1;
    }

    /** Nomor yang tertulis tepat setelah label jenis tertentu, atau null. */
    function nomorBerlabel(teks, jenis) {
        var pola = LABEL[jenis] || [];
        for (var i = 0; i < pola.length; i++) {
            var m = teks.match(pola[i]);
            if (m) {
                var n = digit(m[1]);
                if (panjangSah(n, jenis)) return n;

                // Deret bisa kepanjangan karena menyerempet angka kolom
                // berikutnya, dan itu boleh dipotong. Tapi selisih kecil
                // BUKAN kelebihan tangkap — itu tanda nomornya memang format
                // lain: NPWP 16 digit yang dipotong jadi 15 akan tampak sahih
                // padahal salah, dan diam-diam masuk ke kolom yang keliru.
                var urut = (PANJANG[jenis] || [16]).slice().sort(function (a, b) { return b - a; });
                for (var p = 0; p < urut.length; p++) {
                    if (n.length >= urut[p] + 3) return n.slice(0, urut[p]);
                }
            }
        }
        return null;
    }

    /**
     * Cari nomor di dalam teks (hasil OCR maupun lapisan teks PDF).
     *
     * Label diutamakan; baru kalau tak ada label yang cocok, jatuh ke
     * pencocokan panjang — dan pada tahap itu nomor yang jelas MILIK jenis
     * lain dibuang lebih dulu, supaya tidak mengambil NIK saat yang dicari
     * nomor KK.
     */
    function cariNomor(teks, jenis, teksBerlabel) {
        var berlabel = nomorBerlabel(teks, jenis);
        if (berlabel) return berlabel;

        // Nomor milik jenis lain — jangan sampai terambil. Konteks label
        // boleh datang dari teks LAIN: pada percobaan kedua yang keluarannya
        // hanya angka, labelnya sudah tidak ada, jadi dipakai teks percobaan
        // pertama yang masih memuatnya.
        var sumberLabel = teksBerlabel || teks;
        var terlarang = {};
        for (var j in LABEL) {
            if (j === jenis) continue;
            var lain = nomorBerlabel(sumberLabel, j);
            if (lain) terlarang[lain] = true;
        }

        var kandidat = [], m;
        var re = /\d[\d.\-\s]{8,32}\d/g;
        while ((m = re.exec(teks)) !== null) kandidat.push(digit(m[0]));
        var re2 = /\d{10,20}/g;
        while ((m = re2.exec(teks)) !== null) kandidat.push(m[0]);

        var sah = PANJANG[jenis] || [16];
        for (var i = 0; i < sah.length; i++) {
            for (var k = 0; k < kandidat.length; k++) {
                if (kandidat[k].length === sah[i] && ! terlarang[kandidat[k]]) return kandidat[k];
            }
        }
        return null;
    }

    /** Label jenis ini muncul di teks? Bukti bahwa kartunya memang jenis itu. */
    function adaLabel(teks, jenis) {
        var kata = {
            nik_ktp:   /\bNIK\b/i,
            no_kk:     /KARTU\s*KELUARGA|\bNo\.?\s*KK\b/i,
            no_npwp:   /\bNPWP/i,
            no_npwp16: /\bNPWP\s*[-]?\s*16\b/i
        };
        return kata[jenis] ? kata[jenis].test(teks) : false;
    }

    /**
     * Ambil nomor dari hasil pengenalan KHUSUS ANGKA, hanya bila cukup aman.
     *
     * Keluaran khusus angka kehilangan seluruh label, jadi tak ada cara
     * memastikan deretan mana yang benar. Tanpa pengetatan, derau pada kartu
     * NPWP sempat menghasilkan 669249122504525 — 15 digit, tampak sahih,
     * tapi salah. Angka keliru yang terisi otomatis lebih berbahaya daripada
     * kolom kosong, apalagi nomor ini wajib dan karyawan cenderung menerima
     * apa yang sudah terisi.
     *
     * Dua syarat yang harus dipenuhi:
     *   1. Label jenisnya terlihat di hasil pengenalan pertama — bukti kita
     *      memang sedang melihat kartu yang dimaksud.
     *   2. Hanya ADA SATU kandidat berpanjang sah. Lebih dari satu berarti
     *      kita menebak, dan menebak nomor identitas tidak dapat diterima.
     */
    function tebakAman(teksAngka, jenis, teksBerlabel) {
        if (! adaLabel(teksBerlabel, jenis)) return null;

        var terlarang = {};
        for (var j in LABEL) {
            if (j === jenis) continue;
            var lain = nomorBerlabel(teksBerlabel, j);
            if (lain) terlarang[lain] = true;
        }

        var sah = PANJANG[jenis] || [16];
        var unik = {}, m;
        var re = /\d{10,25}/g;
        while ((m = re.exec(teksAngka)) !== null) {
            for (var i = 0; i < sah.length; i++) {
                if (m[0].length === sah[i] && ! terlarang[m[0]]) unik[m[0]] = true;
            }
        }

        var daftar = Object.keys(unik);
        return daftar.length === 1 ? daftar[0] : null;
    }

    /**
     * Kenali gambar yang SUDAH diperkecil, dengan percobaan kedua khusus angka
     * bila percobaan pertama gagal. Dipakai bersama jalur foto dan jalur PDF
     * hasil pindai — keduanya menghadapi salah-baca angka yang sama.
     */
    function kenaliGambar(blob, jenis, lapor) {
        return siapkanWorker(lapor)
            .then(function (w) { return w.recognize(blob); })
            .then(function (hasil) {
                var teks  = (hasil && hasil.data && hasil.data.text) || '';
                var nomor = cariNomor(teks, jenis);
                if (nomor) return { nomor: nomor, teks: teks, sumber: 'ocr' };

                if (lapor) lapor(100);
                return bacaHanyaAngka(blob)
                    .then(function (angka) {
                        return { nomor: tebakAman(angka, jenis, teks), teks: teks, sumber: 'ocr' };
                    })
                    .catch(function () {
                        return { nomor: null, teks: teks, sumber: 'ocr' };
                    });
            });
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
                    // Lewat jalur yang sama dengan foto, termasuk percobaan
                    // kedua khusus angka.
                    return pdfKeGambar(halaman)
                        .then(function (blob) { return kecilkan(blob, 2400); })
                        .then(function (kecil) { return kenaliGambar(kecil, jenis, lapor); });
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

        return kecilkan(file, 2400).then(function (blob) {
            return kenaliGambar(blob, jenis, lapor);
        });
    }

    global.OcrIdentitas = { baca: baca };
})(window);
