--SEPARATOR=@
--UP
/*M2:UP*/
ALTER TABLE tmp_blade_migrations ADD COLUMN test_col2 INT NOT NULL DEFAULT 22@
ALTER TABLE tmp_blade_migrations DROP COLUMN test_col1@

--DOWN
/*M2:DOWN*/
ALTER TABLE tmp_blade_migrations ADD COLUMN test_col1 INT NOT NULL DEFAULT 11@
ALTER TABLE tmp_blade_migrations DROP COLUMN test_col2@
