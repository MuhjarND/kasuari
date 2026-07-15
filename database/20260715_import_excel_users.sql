-- Import 11 akun dari data bar.xlsx.
-- Aman dijalankan ulang: username atau email yang sudah ada tidak digandakan.
-- Seluruh akun dibuat sebagai Pengguna (group=1), bukan Administrator.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Dr Acep Saifuddin, S.H., M.Ag.', 'nip196402051992031005', '$2y$10$VyGnKdx1t7DDlt4VlwRVt.Npmmf0SfQT0mS1TQxG1wQ8Ogv8WFLjS', 1, '196402051992031005@kasuari.local', '081324500445', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196402051992031005' OR email='196402051992031005@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Dr. Drs Muhlas, S.H., M.H.', 'nip196604301992031001', '$2y$10$E.X8fw2Iu7ey0ga1FAm7UOZXRbe/lP9tToagrTuEI0ahAAxjoKwuW', 1, '196604301992031001@kasuari.local', '082334207775', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196604301992031001' OR email='196604301992031001@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Syafrudin Mohamad, M.H.', 'nip196406121992021001', '$2y$10$yA3R3DK6fWZbdwn9ftRnhOLPMiIiQbA1LiMlB0fVuVJT2i5cgkgYC', 1, '196406121992021001@kasuari.local', '085241592525', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196406121992021001' OR email='196406121992021001@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Mahzumi, M.H.', 'nip196604141994031006', '$2y$10$nhcf9h9NfNTr73gnDiRcTOD35iv.zipzXsxv/Whu0e9VLXmz7y0DC', 1, '196604141994031006@kasuari.local', '081259680466', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196604141994031006' OR email='196604141994031006@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs Ihsan, M.H.', 'nip196605291994021001', '$2y$10$Olu1yJ1tZmK8TxZHj9SqtuG4YuTcIcfPztibQIx/JiZq2HQOqGKqK', 1, '196605291994021001@kasuari.local', '089628806116', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196605291994021001' OR email='196605291994021001@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Komsun, S.H., M.H.E.S.', 'nip196707151993031005', '$2y$10$bn1RoFN28ztEo6vTUWEjrOCsxDKIr59GMyQbeRU2xOE2u2E2YGc3K', 1, '196707151993031005@kasuari.local', '081336684279', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196707151993031005' OR email='196707151993031005@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Khotibul Umam', 'nip196709151993031003', '$2y$10$Ij7y80xn4KxUDJrcpRD3feYLeBXYbrFfGd2g52IQL3d0HhsTbxtA6', 1, '196709151993031003@kasuari.local', '081329092709', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196709151993031003' OR email='196709151993031003@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Dindin Syarief Nurwahyudin', 'nip196711121993031003', '$2y$10$ymQpaTzJSwHfvMHCxbXLOOPhgdBXjqA.VkNOi.wA4Pyz1FdgXv8RC', 1, '196711121993031003@kasuari.local', '081362172631', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196711121993031003' OR email='196711121993031003@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. H. Masnun, S.H.', 'nip196712101992031001', '$2y$10$HEv/dzdUJeuQ6GEX6FGzEuSeTkRIV7hQPrrqOweiIA1hhIOFMa0Qq', 1, '196712101992031001@kasuari.local', '081313975997', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196712101992031001' OR email='196712101992031001@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Syamsul Bahri, M.H.', 'nip196712311994031051', '$2y$10$RUnYpAQwohqvFv8Oz3/hFOXSdsk7KZot6ZAgQazFtK461VcxJcPEK', 1, '196712311994031051@kasuari.local', '085299452536', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196712311994031051' OR email='196712311994031051@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Muhammad Iskandar Eko Putro, M.H.', 'nip196910091994031003', '$2y$10$zLBMMRWaEkeYQiD9fCBOUeHZUHNvCr/SJ7WoNKFpOf0x/PDSoYwFm', 1, '196910091994031003@kasuari.local', '081228242322', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196910091994031003' OR email='196910091994031003@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Muhammad Takdir, S.H., M.H.', 'nip196308081989031005', '$2y$10$L7FfgB0gDypkKmdIY7oFouTJrgUiBzBAyUHftltEFTCOMc/iVglGK', 1, '196308081989031005@kasuari.local', '085242703739', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196308081989031005' OR email='196308081989031005@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Rahmat Farid, M.H.', 'nip196510051991031008', '$2y$10$9MeQB4WYPTKuri4ZOtO5celWI7Zyx47ZkZLMF1g.GOmkujeE64e.2', 1, '196510051991031008@kasuari.local', '081345218327', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196510051991031008' OR email='196510051991031008@kasuari.local');

INSERT INTO sys_users (fullname, username, password, `group`, email, no_wa, block, diinput_oleh, diinput_tanggal)
SELECT 'Drs. Basyirun, M.H.', 'nip196308161994031001', '$2y$10$3njhj/d4WeU6PvUcJSErneGwoJj5SpYC93tMicwXVXJ55P6hd4Tse', 1, '196308161994031001@kasuari.local', '081230804025', 0, 'import_excel', NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_users WHERE username='nip196308161994031001' OR email='196308161994031001@kasuari.local');

COMMIT;
