--TRANSACTION
--UP
ALTER TABLE tmp_blade_migrations ADD COLUMN test_col1 INT NOT NULL DEFAULT 11;

--DOWN
ALTER TABLE tmp_blade_migrations DROP COLUMN test_col1;
