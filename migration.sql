-- Ddrop and recreate the sequence
DO $$
DECLARE
    max_id integer;
BEGIN
    SELECT MAX(wsrid) INTO max_id FROM tbl_wsrrecord;
    DROP SEQUENCE IF EXISTS seq_wsr;
    EXECUTE format('CREATE SEQUENCE seq_wsr START %s', COALESCE(max_id + 1, 1));
END $$;

-- Drop existing indexes if they exist
DROP INDEX IF EXISTS idx_invrecord_partcode_date;
DROP INDEX IF EXISTS idx_presswork_composite;
DROP INDEX IF EXISTS idx_presspart_status_exchangetime;
DROP INDEX IF EXISTS idx_presspart_partcode;
DROP INDEX IF EXISTS idx_inventory_partcode;

-- Drop existing columns if they exist (for exchange work table)
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_name = 'tbl_exchangework' AND column_name = 'employeecode') THEN
        ALTER TABLE tbl_exchangework DROP COLUMN employeecode;
    END IF;
    
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_name = 'tbl_exchangework' AND column_name = 'employeename') THEN
        ALTER TABLE tbl_exchangework DROP COLUMN employeename;
    END IF;
END $$;

-- Table alterations
ALTER TABLE tbl_wsrrecord
    ALTER COLUMN staffnames TYPE text;

ALTER TABLE tbl_exchangework
    ADD employeecode VARCHAR(10),
    ADD employeename VARCHAR(30);

ALTER TABLE tbl_employee
    ADD employeecode VARCHAR(10),
    ADD employeename VARCHAR(30);


ALTER TABLE tbl_employee_press
    ADD employeecode VARCHAR(10),
    ADD employeename VARCHAR(30);

-- Create indexes
CREATE INDEX idx_invrecord_partcode_date ON tbl_invrecord(partcode, jobdate);
CREATE INDEX idx_presswork_composite ON tbl_presswork(machineno, model, dieno, dieunitno, startdatetime);
CREATE INDEX idx_presspart_status_exchangetime ON mas_presspart(status, exchangedatetime);
CREATE INDEX idx_presspart_partcode ON mas_presspart(partcode);
CREATE INDEX idx_inventory_partcode ON mas_inventory(partcode);

