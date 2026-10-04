# Technical Debt Register (DESIGN-03)

Dokumen ini mencatat secara transparan dan jujur keterbatasan sistem saat ini, kompromi desain yang diambil karena batasan waktu dan lingkup purwarupa, serta rencana perbaikan ideal di masa mendatang.

---

## Daftar Utang Teknis (Technical Debt Register)

| ID | Komponen / Area | Keterbatasan / Jalan Pintas Saat Ini | Dampak | Rencana Solusi Ideal Mendatang |
| :--- | :--- | :--- | :--- | :--- |
| **TD-01** | Routing HTTP | Menggunakan `public/index.php` dengan associative array routing dan regex match sederhana | Cukup untuk ~25 rute saat ini, namun akan sulit diskalakan jika aplikasi berkembang menjadi ratusan rute RESTful. | Mengembangkan `Router` kelas mandiri ringan berbasis FastRoute atau Trie-matcher tanpa framework eksternal. |
| **TD-02** | Template Rendering | Menggunakan PHP template native murni (`require 'templates/dashboard.php'`) dengan satu file dashboard monolitik. | File dashboard menjadi besar (~1.800 baris) karena menggabungkan modal dan tab untuk seluruh entitas. | Memecah template ke dalam komponen-komponen terpisah (*partials*): `partials/header.php`, `partials/orders_table.php`, `partials/modal_so.php`. |
| **TD-03** | Batch Item Validation | Validasi array item pesanan (kuantitas dan harga) dilakukan secara perulangan di dalam `Service`. | Duplikasi validasi dasar pada pembuatan SO dan PO. | Mengekstrak kelas Value Object `OrderItemCollection` yang otomatis memvalidasi kuantitas positif dan total nilai item. |
| **TD-04** | Pagination Database | Menggunakan query `SELECT ... LIMIT 10 OFFSET ?` konvensional. | Performa query efisien untuk puluhan ribu data, namun mulai melambat (*full scan offset*) jika data mencapai jutaan baris. | Menerapkan *keyset pagination* (Cursor-based pagination: `WHERE id < ? ORDER BY id DESC LIMIT 10`) untuk tabel `stock_ledger` yang bervolume tinggi. |
| **TD-05** | Distributed Lock | Penguncian stok menggunakan Pessimistic Locking tingkat database (`SELECT ... FOR UPDATE`). | Sangat handal dan aman untuk satu instance database relasional, namun belum terdistribusi jika menggunakan multi-master cluster. | Menjaga database relasional tunggal sebagai *single source of truth* untuk mutasi saldo, atau menambahkan distributed lock (Redis Redlock) jika arsitektur multi-datacenter dibutuhkan di masa depan. |
