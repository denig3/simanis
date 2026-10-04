# BUKTI TEKNIS & CADANGAN (OFFLINE EVIDENCE & BACKUP)

Folder ini berisi berkas cadangan (*fallback evidence*) yang disiapkan untuk memenuhi ketentuan pengujian teknis dan cadangan jika terjadi gangguan perangkat/jaringan saat presentasi:

---

## 1. Cadangan Tangkapan Layar Antarmuka (UI Screenshots)
1. **[01_dashboard_executive_summary.png](01_dashboard_executive_summary.png)**: Tampilan ringkasan dashboard eksekutif (Valuasi Modal Rp 925jt, Nilai Jual Rp 1.2M, Omzet SO Aktif Rp 1.68M, Antrean SOD).
2. **[02_dashboard_operational_recap.png](02_dashboard_operational_recap.png)**: Rekapitulasi status pesanan Sales Order, Purchase Order, dan Sebaran Unit per Gudang (JKT, SBY, BDG).
3. **[03_login_page_simanis.png](03_login_page_simanis.png)**: Antarmuka login aman SIMANIS dengan branding profesional.

---

## 2. Bukti Kualitas Kode & Pengujian Otomatis
4. **[04_sonarqube_quality_gate.png](04_sonarqube_quality_gate.png)**: Tangkapan layar SonarQube v26.9 berstatus **Quality Gate: Passed**.
5. **[05_phpunit_test_results.txt](05_phpunit_test_results.txt)**: Log hasil pengujian 31/31 test PHPUnit (100% Passed, 147 assertions).
6. **[06_phpstan_analysis.txt](06_phpstan_analysis.txt)**: Log hasil analisis statis kode PHPStan Level 6 (0 errors across 67 files).
7. **[07_sonarqube_qualitygate_api.json](07_sonarqube_qualitygate_api.json)**: Respons JSON resmi SonarQube Server (`status: OK`, `new_violations: 0`).
