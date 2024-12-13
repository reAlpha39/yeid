-- existing mas_machine
-- machineno, machinename, shopcode, shopname, linename

-- existing mas_employee
-- employeecode, employeename, role

-- Create activities table
CREATE TABLE activities (
    activity_id INT PRIMARY KEY,
    machineno INT NOT NULL,
    activity_name VARCHAR(128) NOT NULL,
    FOREIGN KEY (machineno) REFERENCES mas_machine(machineno)
);

-- Create activity_schedule table
CREATE TABLE activity_schedule (
    schedule_id INT PRIMARY KEY,
    activity_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    total_tasks INT NOT NULL DEFAULT 0,
    completed_on_time INT NOT NULL DEFAULT 0,
    completed_delayed INT NOT NULL DEFAULT 0,
    not_completed INT NOT NULL DEFAULT 0,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id),
    CONSTRAINT check_totals
        CHECK (total_tasks = completed_on_time + completed_delayed + not_completed),
    CONSTRAINT valid_month
        CHECK (month BETWEEN 1 AND 12)
);

-- Create tasks table
CREATE TABLE tasks (
    task_id INT PRIMARY KEY,
    activity_id INT NOT NULL,
    task_name VARCHAR(200) NOT NULL,
    frequency_times INT NOT NULL,
    frequency_period VARCHAR(10) NOT NULL,
    start_week INT NOT NULL,
    duration INT NOT NULL,
    manpower_required INT NOT NULL DEFAULT 1,
    pic VARCHAR(128), 
    cycle_time INT,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id),
    CONSTRAINT valid_frequency_period 
        CHECK (frequency_period IN ('week', 'month', 'year')),
    CONSTRAINT valid_frequency_times
        CHECK (frequency_times > 0),
    CONSTRAINT valid_start_week
        CHECK (start_week BETWEEN 1 AND 53)
);

-- Create task_schedule table
CREATE TABLE task_schedule (
    schedule_id INT PRIMARY KEY,
    task_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    completion_date DATE,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id),
    CONSTRAINT valid_dates 
        CHECK (completion_date IS NULL OR completion_date >= scheduled_date),
    CONSTRAINT valid_status 
        CHECK (status IN ('Pending', 'Completed', 'Delayed', 'Cancelled'))
);

-- Create personnel_assignments table
CREATE TABLE employee_assignments (
    assignment_id INT PRIMARY KEY,
    employeecode INT NOT NULL,
    task_schedule_id INT NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employeecode) REFERENCES mas_employee(employeecode),
    FOREIGN KEY (task_schedule_id) REFERENCES task_schedule(schedule_id)
);

-- Create indexes for better query performance
CREATE INDEX idx_activity_schedule_month_year ON activity_schedule(month, year);
CREATE INDEX idx_task_schedule_date ON task_schedule(scheduled_date);
CREATE INDEX idx_task_schedule_status ON task_schedule(status);
CREATE INDEX idx_personnel_assignments_date ON personnel_assignments(assigned_date);

-- Create view for monthly completion rates
CREATE VIEW monthly_completion_rates AS
SELECT 
    m.machine_name,
    a.activity_name,
    as.month,
    as.year,
    as.total_tasks,
    as.completed_on_time,
    CASE 
        WHEN as.total_tasks > 0 
        THEN (as.completed_on_time::DECIMAL / as.total_tasks * 100)
        ELSE 0 
    END as completion_rate
FROM activity_schedule as
JOIN activities a ON as.activity_id = a.activity_id
JOIN machines m ON a.machine_id = m.machine_id;
