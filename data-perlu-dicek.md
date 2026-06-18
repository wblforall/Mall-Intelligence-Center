# Data Karyawan — Yang Perlu Dicek / Dibetulkan

Hasil impor `DATA KARYAWAN .xlsx` (201 karyawan) ke database. Tanggal: 2026-06-18.

## A. WAJIB dibetulkan — error NIK (2 orang)
File salah menaruh NIK; sudah di-NULL-kan + diberi catatan di record-nya.

| Nama | Dept | Masalah | NIK benar |
|---|---|---|---|
| Riska Fitriani | Creative Concept & Design | NIK kosong | file salah pakai NIK Dhia `231217145` |
| Reza Ilyasa' | HR-GA & Legal | NIK kosong | file salah pakai NIK Axel `19080802` |

> NIK `231217145` = milik **Muhammad Dhia Ulhaq** (Engineering, join 17-12-2023).
> NIK `19080802` = milik **Axel Riando Otniel** (Engineering, join 02-08-2019).

## B. Potensi salah cabang Org Chart — Engineering (2 orang)
Staff Engineering di-split ke cabang eWalk/Pentacity memakai kolom PROJECT
(yang ternyata = sumber gaji/payroll). 2 orang ini title-nya jelas "e-Walk"
tapi payroll-nya Pentacity, jadi tertempatkan di cabang Pentacity.

| Nama | Title | Cabang sekarang | Mungkin harusnya |
|---|---|---|---|
| Ahmad Rega Yusuf | Teknisi AC e-Walk | Pentacity | eWalk |
| Rahmadani | Teknisi Elektrikal e-Walk | Pentacity | eWalk |

## C. Project (payroll) ≠ mall org — WAJAR (bukan error)
Project = sumber gaji, bisa beda dari mall tim yang dipimpin/ditempati.
Tidak perlu diubah kecuali memang ingin disamakan.

- Yusri Yusuf (org Pentacity, payroll eWalk)
- Shinta Koemara (org Pentacity, payroll eWalk)
- Rosita (org eWalk, payroll Pentacity)
- Rizky Ananda Pratiwi (org eWalk, payroll Pentacity)
- Novi Rahmayanti Damanik (org Pentacity, payroll eWalk)
- Pimpinan Engineering: Epi Hari S, Budiman, Hardianto, Laode Haitul Bariya

## D. Typo title (kosmetik, opsional)
- "Teknisi Ac" → "Teknisi AC"
- "Teknisi Elekrtikal" → "Teknisi Elektrikal"
- "SPV Purchaisng" → "SPV Purchasing"
- dll.

## Catatan lain
- **GM & Direktur** tidak ada di file → puncak org = Deputy GM (vacant di atasnya).
- **`user_id` (akun login)** belum di-set per karyawan.
- **Tidak ada tanggal habis kontrak** di file — hanya status kontrak.
