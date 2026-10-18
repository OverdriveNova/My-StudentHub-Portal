USE studenthub;

-- Admin user (password: Admin@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin DEPSTAR', 'admin@charusat.ac.in', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 20 student users (password for all: Student@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Rudra Modi',      'rudra.modi@charusat.edu.in',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Hemin Shah',      'hemin.shah@charusat.edu.in',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Parth Patel',     'parth.patel@charusat.edu.in',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Ananya Desai',    'ananya.desai@charusat.edu.in',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Dhruv Trivedi',   'dhruv.trivedi@charusat.edu.in',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Priya Mehta',     'priya.mehta@charusat.edu.in',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Karan Joshi',     'karan.joshi@charusat.edu.in',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Sneha Rao',       'sneha.rao@charusat.edu.in',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Arjun Nair',      'arjun.nair@charusat.edu.in',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Pooja Sharma',    'pooja.sharma@charusat.edu.in',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Vivek Gupta',     'vivek.gupta@charusat.edu.in',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Nisha Agarwal',   'nisha.agarwal@charusat.edu.in',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Rahul Verma',     'rahul.verma@charusat.edu.in',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Tanvi Kapoor',    'tanvi.kapoor@charusat.edu.in',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Siddharth Das',   'siddharth.das@charusat.edu.in',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Meera Iyer',      'meera.iyer@charusat.edu.in',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Ravi Pillai',     'ravi.pillai@charusat.edu.in',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Zara Khan',       'zara.khan@charusat.edu.in',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Aditya Singh',    'aditya.singh@charusat.edu.in',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Kavya Reddy',     'kavya.reddy@charusat.edu.in',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Student profiles
INSERT IGNORE INTO students (user_id, enrollment_no, course, semester, year, mobile, cgpa, dob) VALUES
(2,  '25DCS058', 'CSE', 3, 2, '8530001001', 8.90, '2006-03-15'),
(3,  '25DIT019', 'IT',  3, 2, '9876543210', 8.20, '2006-07-22'),
(4,  '25DCS071', 'CSE', 3, 2, '7654321098', 9.10, '2006-01-10'),
(5,  '25DCS042', 'CSE', 3, 2, '9123456789', 7.85, '2006-05-18'),
(6,  '25DCS033', 'CSE', 3, 2, '8901234567', 8.50, '2006-09-03'),
(7,  '25DIT055', 'IT',  3, 2, '7890123456', 7.60, '2006-11-25'),
(8,  '25DCS029', 'CSE', 3, 2, '9012345678', 8.75, '2006-02-14'),
(9,  '25DCS088', 'CSE', 3, 2, '8123456789', 9.20, '2006-08-30'),
(10, '25DIT037', 'IT',  3, 2, '7012345678', 7.40, '2006-06-12'),
(11, '25DCS064', 'CSE', 3, 2, '9234567890', 8.10, '2006-04-07'),
(12, '25DCS051', 'CSE', 3, 2, '8345678901', 7.90, '2006-12-20'),
(13, '25DIT028', 'IT',  3, 2, '7456789012', 8.30, '2006-03-28'),
(14, '25DCS076', 'CSE', 3, 2, '9456789012', 7.70, '2006-10-11'),
(15, '25DCS039', 'CSE', 3, 2, '8567890123', 8.60, '2006-07-05'),
(16, '25DCS082', 'CSE', 3, 2, '7567890123', 9.00, '2006-01-30'),
(17, '25DIT044', 'IT',  3, 2, '9678901234', 7.55, '2006-09-17'),
(18, '25DCS025', 'CSE', 3, 2, '8678901234', 8.45, '2006-05-23'),
(19, '25DCS093', 'CSE', 3, 2, '7789012345', 7.25, '2006-11-08'),
(20, '25DCS047', 'CSE', 3, 2, '9789012345', 8.80, '2006-02-19'),
(21, '25DIT062', 'IT',  3, 2, '8890123456', 7.35, '2006-08-14');

-- Events (created by admin user_id=1)
INSERT IGNORE INTO events (title, description, category, venue, event_date, status, created_by) VALUES
('Annual Web Hackathon 2026',           'Build responsive web projects using HTML/CSS/JS. Teams of 2-4 students.', 'Technical', 'Seminar Hall A', '2026-08-15', 'Open', 1),
('Cloud Computing Workshop',            'Hands-on session on AWS and GCP fundamentals.', 'Technical', 'Lab Block 3', '2026-08-20', 'Limited Seats', 1),
('Inter-College Cricket Tournament',    'Annual sports meet. Register your team of 11.', 'Sports', 'CHARUSAT Ground', '2026-09-05', 'Open', 1),
('Cultural Fest Tarang 2026',           'Annual cultural celebration with dance, music, and drama.', 'Cultural', 'Open Amphitheatre', '2026-09-15', 'Coming Soon', 1),
('Machine Learning Bootcamp',           '3-day intensive ML bootcamp for beginners.', 'Technical', 'CS Department Hall', '2026-08-28', 'Open', 1),
('Career Fair 2026',                    'Top companies visiting campus for placements.', 'Career', 'Main Auditorium', '2026-10-10', 'Coming Soon', 1),
('Basketball Championship',             'Inter-department basketball league.', 'Sports', 'Indoor Sports Complex', '2026-09-20', 'Open', 1),
('Photography Workshop',                'Learn DSLR photography basics and photo editing.', 'Cultural', 'Media Lab', '2026-08-22', 'Limited Seats', 1),
('Cybersecurity Seminar',               'Expert talk on ethical hacking and network security.', 'Technical', 'Seminar Hall B', '2026-09-01', 'Open', 1),
('Startup Ideathon',                    'Pitch your startup idea and win mentorship sessions.', 'Technical', 'Innovation Hub', '2026-09-25', 'Open', 1);

-- Registrations
INSERT IGNORE INTO registrations (student_id, event_id) VALUES
(1,1),(1,2),(1,5),(2,1),(2,3),(3,1),(3,4),(4,2),(4,5),(5,6),
(6,1),(7,3),(8,9),(9,10),(10,1),(11,2),(12,4),(13,5),(14,7),(15,8);

-- Audit log entries
INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES
(1, 'ADMIN_LOGIN',       'Admin logged into the system', '127.0.0.1'),
(2, 'USER_REGISTERED',   'New student account created for rudra.modi@charusat.edu.in', '127.0.0.1'),
(3, 'USER_REGISTERED',   'New student account created for hemin.shah@charusat.edu.in', '127.0.0.1'),
(1, 'EVENT_CREATED',     'New event: Annual Web Hackathon 2026', '127.0.0.1'),
(2, 'EVENT_REGISTERED',  'Student registered for event ID 1', '127.0.0.1');
