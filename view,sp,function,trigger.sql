USE proyek_pbd;


CREATE OR REPLACE VIEW v_pengadaan AS
SELECT idpengadaan, `timestamp`, user_iduser, status AS status_pengadaan,
       vendor_idvendor, subtotal_nilai, ppn, total_nilai
FROM pengadaan;

CREATE OR REPLACE VIEW v_detail_pengadaan AS
SELECT iddetail_pengadaan, harga_satuan, jumlah, sub_total, idbarang, idpengadaan
FROM detail_pengadaan;

CREATE OR REPLACE VIEW v_penerimaan AS
SELECT idpenerimaan, created_at, status AS status_penerimaan, idpengadaan, iduser
FROM penerimaan;

CREATE OR REPLACE VIEW v_detail_penerimaan AS
SELECT iddetail_penerimaan, idpenerimaan, barang_idbarang, jumlah_terima,
       harga_satuan_terima, sub_total_terima
FROM detail_penerimaan;

CREATE OR REPLACE VIEW v_penjualan AS
SELECT idpenjualan, created_at, subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan
FROM penjualan;

CREATE OR REPLACE VIEW v_detail_penjualan AS
SELECT iddetail_penjualan, harga_satuan, jumlah, subtotal, penjualan_idpenjualan, idbarang
FROM detail_penjualan;

CREATE OR REPLACE VIEW v_kartu_stok AS
SELECT idkartu_stok, jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang
FROM kartu_stok;

CREATE OR REPLACE VIEW v_retur AS
SELECT idretur, created_at, idpenerimaan, iduser
FROM retur;

CREATE OR REPLACE VIEW v_detail_retur AS
SELECT iddetail_retur, jumlah, alasan, idretur, iddetail_penerimaan
FROM detail_retur;


DELIMITER $$

DROP FUNCTION IF EXISTS fn_stok_barang$$
CREATE FUNCTION fn_stok_barang(p_idbarang INT) RETURNS INT
READS SQL DATA
BEGIN
  RETURN IFNULL((SELECT stock FROM kartu_stok
                 WHERE idbarang = p_idbarang
                 ORDER BY idkartu_stok DESC LIMIT 1), 0);
END$$

DROP FUNCTION IF EXISTS fn_harga_jual$$
CREATE FUNCTION fn_harga_jual(p_idbarang INT, p_idmargin INT) RETURNS INT
READS SQL DATA
BEGIN
  RETURN (SELECT ROUND(b.harga * (1 + m.persen / 100))
          FROM barang b, margin_penjualan m
          WHERE b.idbarang = p_idbarang
            AND m.idmargin_penjualan = p_idmargin);
END$$

DROP PROCEDURE IF EXISTS sp_tambah_pengadaan$$
CREATE PROCEDURE sp_tambah_pengadaan(
  IN p_iduser INT, IN p_idvendor INT, IN p_idbarang INT, IN p_jumlah INT)
BEGIN
  DECLARE v_harga INT;
  DECLARE v_sub INT;
  DECLARE v_ppn INT;
  DECLARE v_id BIGINT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_jumlah <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah harus lebih dari 0';
  END IF;

  SELECT harga INTO v_harga FROM barang WHERE idbarang = p_idbarang;
  IF v_harga IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barang tidak ditemukan';
  END IF;

  SET v_sub = v_harga * p_jumlah;
  SET v_ppn = ROUND(v_sub * 0.11);

  START TRANSACTION;
  INSERT INTO pengadaan (user_iduser, status, vendor_idvendor, subtotal_nilai, ppn, total_nilai)
  VALUES (p_iduser, 'P', p_idvendor, v_sub, v_ppn, v_sub + v_ppn);
  SET v_id = LAST_INSERT_ID();
  INSERT INTO detail_pengadaan (harga_satuan, jumlah, sub_total, idbarang, idpengadaan)
  VALUES (v_harga, p_jumlah, v_sub, p_idbarang, v_id);
  COMMIT;
END$$

DROP PROCEDURE IF EXISTS sp_terima_pengadaan$$
CREATE PROCEDURE sp_terima_pengadaan(IN p_iduser INT, IN p_idpengadaan BIGINT)
BEGIN
  DECLARE v_status CHAR(1);
  DECLARE v_id BIGINT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  SELECT status INTO v_status FROM pengadaan WHERE idpengadaan = p_idpengadaan;
  IF v_status IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengadaan tidak ditemukan';
  END IF;
  IF v_status = 'S' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengadaan ini sudah diterima';
  END IF;

  START TRANSACTION;
  INSERT INTO penerimaan (status, idpengadaan, iduser)
  VALUES ('S', p_idpengadaan, p_iduser);
  SET v_id = LAST_INSERT_ID();

  INSERT INTO detail_penerimaan
    (idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima)
  SELECT v_id, idbarang, jumlah, harga_satuan, sub_total
  FROM detail_pengadaan WHERE idpengadaan = p_idpengadaan;

  UPDATE pengadaan SET status = 'S' WHERE idpengadaan = p_idpengadaan;
  COMMIT;
END$$

DROP PROCEDURE IF EXISTS sp_tambah_penjualan$$
CREATE PROCEDURE sp_tambah_penjualan(
  IN p_iduser INT, IN p_idmargin INT, IN p_idbarang INT, IN p_jumlah INT)
BEGIN
  DECLARE v_harga INT;
  DECLARE v_sub INT;
  DECLARE v_ppn INT;
  DECLARE v_id INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_jumlah <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah harus lebih dari 0';
  END IF;

  SET v_harga = fn_harga_jual(p_idbarang, p_idmargin);
  IF v_harga IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barang atau margin tidak ditemukan';
  END IF;

  IF fn_stok_barang(p_idbarang) < p_jumlah THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok tidak cukup';
  END IF;

  SET v_sub = v_harga * p_jumlah;
  SET v_ppn = ROUND(v_sub * 0.11);

  START TRANSACTION;
  INSERT INTO penjualan (subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan)
  VALUES (v_sub, v_ppn, v_sub + v_ppn, p_iduser, p_idmargin);
  SET v_id = LAST_INSERT_ID();
  INSERT INTO detail_penjualan (harga_satuan, jumlah, subtotal, penjualan_idpenjualan, idbarang)
  VALUES (v_harga, p_jumlah, v_sub, v_id, p_idbarang);
  COMMIT;
END$$

DROP TRIGGER IF EXISTS trg_penerimaan_stok$$
CREATE TRIGGER trg_penerimaan_stok AFTER INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN
  DECLARE v_stok INT;
  SET v_stok = fn_stok_barang(NEW.barang_idbarang);
  INSERT INTO kartu_stok (jenis_transaksi, masuk, keluar, stock, idtransaksi, idbarang)
  VALUES ('M', NEW.jumlah_terima, 0, v_stok + NEW.jumlah_terima,
          NEW.idpenerimaan, NEW.barang_idbarang);
END$$

DROP TRIGGER IF EXISTS trg_penjualan_stok$$
CREATE TRIGGER trg_penjualan_stok AFTER INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
  DECLARE v_stok INT;
  SET v_stok = fn_stok_barang(NEW.idbarang);
  INSERT INTO kartu_stok (jenis_transaksi, masuk, keluar, stock, idtransaksi, idbarang)
  VALUES ('K', 0, NEW.jumlah, v_stok - NEW.jumlah,
          NEW.penjualan_idpenjualan, NEW.idbarang);
END$$

DELIMITER ;


GRANT EXECUTE ON proyek_pbd.* TO 'pbd_user'@'localhost';
SHOW TRIGGERS;
SHOW PROCEDURE STATUS WHERE Db = 'proyek_pbd';
SHOW FUNCTION STATUS WHERE Db = 'proyek_pbd';