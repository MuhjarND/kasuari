-- Mengubah nama tampilan template tanpa mengubah kode/nama file RTF.
-- Dengan demikian dokumen lama tetap dapat dibuka dan dicetak.

START TRANSACTION;

UPDATE jenis_blangko
SET jenis_blangko_nama = 'Catatan Pertama'
WHERE jenis_blangko_id = 21 OR jenis_blangko_nama = 'BAS Pertama';

UPDATE jenis_blangko
SET jenis_blangko_nama = 'Catatan Lanjutan'
WHERE jenis_blangko_id = 27 OR TRIM(jenis_blangko_nama) = 'BAS Lanjutan';

UPDATE template_dokumen
SET nama = '10. Catatan Pertama'
WHERE id = 28 OR kode = '10. BAS Pertama';

UPDATE template_dokumen
SET nama = '11. Catatan Lanjutan'
WHERE id = 29 OR kode = '11. Bas Lanjutan';

COMMIT;
