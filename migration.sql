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
    ADD employeecode VARCHAR(8),
    ADD employeename VARCHAR(30);

-- Create indexes
CREATE INDEX idx_invrecord_partcode_date ON tbl_invrecord(partcode, jobdate);
CREATE INDEX idx_presswork_composite ON tbl_presswork(machineno, model, dieno, dieunitno, startdatetime);
CREATE INDEX idx_presspart_status_exchangetime ON mas_presspart(status, exchangedatetime);
CREATE INDEX idx_presspart_partcode ON mas_presspart(partcode);
CREATE INDEX idx_inventory_partcode ON mas_inventory(partcode);

-- Delete existing records before insert
DELETE FROM public.mas_department WHERE code = 'MTC';
DELETE FROM public.mas_user WHERE email = 'alpaca4@mail.com';

-- Insert department data
INSERT INTO public.mas_department
    (code, name, created_at, updated_at, deleted_at)
VALUES
    ('MTC', 'MAINTENANCE', '2024-10-03 02:25:28.480', '2025-02-10 22:59:48.000', NULL);

-- Insert user data
INSERT INTO public.mas_user
    (name, email, phone, department_id, role_access, status, control_access, 
     created_at, updated_at, deleted_at, password, remember_token, email_verified_at, nik)
VALUES
    ('Alpaca4', 'alpaca4@mail.com', '1234', 1, '2', '1',
    '{"masterData":{"view":true,"create":true,"update":true,"delete":true},
      "user":{"view":true,"create":true,"update":true,"delete":true},
      "masterDataPart":{"view":true,"create":true,"update":true,"delete":true},
      "invControlPartList":{"view":true,"create":true,"update":true,"delete":true},
      "invControlMasterPart":{"view":true,"create":true,"update":true,"delete":true},
      "invControlInbound":{"view":true,"create":true,"update":true,"delete":true},
      "invControlOutbound":{"view":true,"create":true,"update":true,"delete":true},
      "pressShotPartList":{"view":true,"create":true,"update":true,"delete":true},
      "pressShotExcData":{"view":true,"create":true,"update":true,"delete":true},
      "pressShotProdData":{"view":true,"create":true,"update":true,"delete":true},
      "pressShotMasterPart":{"view":true,"create":true,"update":true,"delete":true},
      "pressShotHistoryAct":{"view":true,"create":true,"update":true,"delete":true},
      "mtDbsDeptReq":{"view":true,"create":true,"update":true,"delete":true},
      "mtDbsMtReport":{"view":true,"create":true,"update":true,"delete":true},
      "mtDbsReqWork":{"view":true,"create":true,"update":true,"delete":true},
      "mtDbsDbAnl":{"view":true,"create":true,"update":true,"delete":true},
      "mtDbsSparePart":{"view":true,"create":true,"update":true,"delete":true},
      "schedule":{"view":true,"create":true,"update":true,"delete":true}}',
    '2025-01-21 22:48:10.000', '2025-02-10 22:46:04.000', NULL,
    '$2y$12$FiLPJs9l4ZFU7tfEwbZLI.LC/dM7.jOjMtoMnxzGDreppCGqe/7gO', NULL, NULL, '1234');
