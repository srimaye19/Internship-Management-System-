<?php

$host = "localhost";
$username = "root";
$password = "";
$dbname = "internship_db";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("<div class='alert alert-danger'><strong>System Error:</strong> " . $e->getMessage() . "</div>");
}

$msg = "";
$msg_class = "";

function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

$edit_mode = false;
$edit_id = "";
$edit_student_name = "";
$edit_roll_number = "";
$edit_company_name = "";
$edit_intern_role = "";
$edit_status = "Pending";


if (isset($_POST['save_application'])) {
    $student_name = sanitize_input($_POST['student_name']);
    $roll_number = sanitize_input($_POST['roll_number']);
    $company_name = sanitize_input($_POST['company_name']);
    $intern_role = sanitize_input($_POST['intern_role']);
    $status = isset($_POST['status']) ? sanitize_input($_POST['status']) : 'Pending';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if (empty($student_name) || empty($roll_number) || empty($company_name) || empty($intern_role)) {
        $msg = "All fields are required to process.";
        $msg_class = "alert-danger";
    } else {
        if ($id > 0) {
            
            $stmt = $conn->prepare("UPDATE internship_applications SET student_name=?, roll_number=?, company_name=?, intern_role=?, status=? WHERE id=?");
            $stmt->bind_param("sssssi", $student_name, $roll_number, $company_name, $intern_role, $status, $id);
            if ($stmt->execute()) {
                $msg = "Application updated successfully!";
                $msg_class = "alert-success";
            } else {
                $msg = "Database Error: Update failed.";
                $msg_class = "alert-danger";
            }
            $stmt->close();
        } else {
          
            $stmt = $conn->prepare("INSERT INTO internship_applications (student_name, roll_number, company_name, intern_role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $student_name, $roll_number, $company_name, $intern_role);
            if ($stmt->execute()) {
                $msg = "Application submitted successfully!";
                $msg_class = "alert-success";
            } else {
                $msg = "Database Error: Submission failed.";
                $msg_class = "alert-danger";
            }
            $stmt->close();
        }
    }
}


if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    
    $stmt = $conn->prepare("SELECT * FROM internship_applications WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    
    if ($row_edit = $result_edit->fetch_assoc()) {
        $edit_student_name = $row_edit['student_name'];
        $edit_roll_number = $row_edit['roll_number'];
        $edit_company_name = $row_edit['company_name'];
        $edit_intern_role = $row_edit['intern_role'];
        $edit_status = $row_edit['status'];
    }
    $stmt->close();
}


if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM internship_applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "Record deleted from system storage.";
        $msg_class = "alert-success";
    } else {
        $msg = "Database Error: Delete operation failed.";
        $msg_class = "alert-danger";
    }
    $stmt->close();
}


if (isset($_GET['clear_all_data_records'])) {
    if ($conn->query("TRUNCATE TABLE internship_applications")) {
        $msg = "Database tables cleared cleanly for live testing verification.";
        $msg_class = "alert-success";
    }
}


$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM internship_applications WHERE student_name LIKE ? OR roll_number LIKE ? OR company_name LIKE ? ORDER BY id DESC");
    $search_param = "%" . $search . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM internship_applications ORDER BY id DESC");
}


$count_total = $conn->query("SELECT COUNT(*) as total FROM internship_applications")->fetch_assoc()['total'];
$count_approved = $conn->query("SELECT COUNT(*) as total FROM internship_applications WHERE status='Approved'")->fetch_assoc()['total'];
$count_pending = $conn->query("SELECT COUNT(*) as total FROM internship_applications WHERE status='Pending'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMS Executive Dashboard</title>
    <style>
        :root {
            --brand-color: #4f46e5;
            --brand-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg-gradient); color: var(--text-main); min-height: 100vh; padding: 30px 20px; }
        .container { max-width: 1280px; margin: 0 auto; }
        
        header { margin-bottom: 30px; border-left: 4px solid var(--brand-color); padding-left: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        header h1 { font-size: 2rem; font-weight: 700; letter-spacing: -0.025em; }
        header p { color: var(--text-muted); font-size: 0.95rem; margin-top: 4px; }
        
        .analytics-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--panel-bg); backdrop-filter: blur(12px); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; flex-direction: column; }
        .stat-card span { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .stat-card h3 { font-size: 1.8rem; margin-top: 5px; font-weight: 700; color: #fff; }
        
        .alert { padding: 14px; margin-bottom: 25px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-align: center; backdrop-filter: blur(8px); }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25); }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); }
        
        .dashboard-grid { display: grid; grid-template-columns: 380px 1fr; gap: 30px; }
        @media (max-width: 950px) { .dashboard-grid { grid-template-columns: 1fr; } }
        
        .panel { background: var(--panel-bg); backdrop-filter: blur(12px); border-radius: 14px; border: 1px solid var(--border-color); padding: 25px; height: fit-content; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3); }
        .panel h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 20px; color: #fff; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        
        .input-block { margin-bottom: 16px; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.05em; }
        input, select { width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; font-size: 0.9rem; transition: all 0.2s; }
        input:focus, select:focus { outline: none; border-color: var(--brand-color); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25); }
        
        .action-btn { display: inline-flex; justify-content: center; align-items: center; width: 100%; padding: 12px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn-primary { background: var(--brand-color); color: #fff; }
        .btn-primary:hover { background: var(--brand-hover); }
        .btn-dismiss { background: #334155; color: #cbd5e1; margin-top: 8px; }
        .btn-dismiss:hover { background: #475569; }
        .btn-clear-system { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); padding: 8px 16px; width: auto; font-size: 0.85rem; border-radius: 6px; }
        .btn-clear-system:hover { background: rgba(239, 68, 68, 0.3); }
        
        .btn-mini { width: auto; padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; font-weight: 500; }
        .btn-edit { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .btn-edit:hover { background: rgba(245, 158, 11, 0.25); }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-delete:hover { background: rgba(239, 68, 68, 0.25); }
        
        .scroller { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        th { background: rgba(15, 23, 42, 0.4); text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); letter-spacing: 0.05em; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        td strong { font-size: 0.95rem; color: #fff; }
        td small { font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 2px; }
        
        .status-pill { display: inline-flex; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .pill-Pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .pill-Approved { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .pill-Rejected { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; }

        .filter-form-box { display: flex; gap: 10px; margin-bottom: 15px; width: 100%; }
        .filter-form-box input { padding: 8px 12px; font-size: 0.85rem; }
        .filter-form-box .btn-search { width: auto; padding: 8px 16px; font-size: 0.85rem; border-radius: 8px; background: #334155; color: white; border: 1px solid var(--border-color); cursor: pointer; }
        .filter-form-box .btn-search:hover { background: #475569; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <h1>Internship Hub Dashboard</h1>
            <p>Corporate Recruitment and Application Workspace Pipeline</p>
        </div>
        <a href="index.php?clear_all_data_records=true" class="action-btn btn-clear-system" onclick="return confirm('Erase every application row inside the MySQL database table?');">Reset DB Data</a>
    </header>

    <div class="analytics-strip">
        <div class="stat-card"><span>Total Submissions</span><h3><?php echo $count_total; ?></h3></div>
        <div class="stat-card"><span>Approved Trainees</span><h3><?php echo $count_approved; ?></h3></div>
        <div class="stat-card"><span>Awaiting Review</span><h3><?php echo $count_pending; ?></h3></div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        
        <div class="panel">
            <h2><?php echo $edit_mode ? "⚡ Update Profile Data" : "✨ Create New Profile"; ?></h2>
            <form action="index.php" method="POST" onsubmit="return verifyFormInputs()">
                <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                
                <div class="input-block">
                    <label>Student Name</label>
                    <input type="text" id="student_name" name="student_name" value="<?php echo htmlspecialchars($edit_student_name); ?>" required>
                </div>
                <div class="input-block">
                    <label>Roll Number / UID</label>
                    <input type="text" id="roll_number" name="roll_number" value="<?php echo htmlspecialchars($edit_roll_number); ?>" required>
                </div>
                <div class="input-block">
                    <label>Target Company</label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($edit_company_name); ?>" required>
                </div>
                <div class="input-block">
                    <label>Corporate Role</label>
                    <input type="text" id="intern_role" name="intern_role" value="<?php echo htmlspecialchars($edit_intern_role); ?>" required>
                </div>
                
                <?php if ($edit_mode): ?>
                <div class="input-block">
                    <label>Application Allocation Status</label>
                    <select name="status">
                        <option value="Pending" <?php if($edit_status=='Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Approved" <?php if($edit_status=='Approved') echo 'selected'; ?>>Approved</option>
                        <option value="Rejected" <?php if($edit_status=='Rejected') echo 'selected'; ?>>Rejected</option>
                    </select>
                </div>
                <?php endif; ?>

                <button type="submit" name="save_application" class="action-btn btn-primary">
                    <?php echo $edit_mode ? "Commit Changes" : "Deploy Application"; ?>
                </button>
                
                <?php if ($edit_mode): ?>
                    <a href="index.php" class="action-btn btn-dismiss">Cancel Modification</a>
                <?php endif; ?>
            </form>
        </div>
     
        <div class="panel">
            <h2><span>📋 Active Applications Matrix</span></h2>

            <form action="index.php" method="GET" class="filter-form-box">
                <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Search student name, roll number, or company...">
                <button type="submit" class="btn-search">Filter</button>
                <?php if(isset($_GET['search']) && $_GET['search'] !== ''): ?>
                    <a href="index.php" class="btn-search" style="text-decoration:none; display:inline-flex; align-items:center;">Clear</a>
                <?php endif; ?>
            </form>

            <div class="scroller">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Candidate Detail</th>
                                <th>Organization</th>
                                <th>Target Role</th>
                                <th>Status Metric</th>
                                <th>Operational Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($row['roll_number']); ?></small>
                                    </td>
                                    <td><span style="color: #fff; font-weight: 500;"><?php echo htmlspecialchars($row['company_name']); ?></span></td>
                                    <td><span style="color: var(--text-muted); font-size: 0.9rem;"><?php echo htmlspecialchars($row['intern_role']); ?></span></td>
                                    <td>
                                        <span class="status-pill pill-<?php echo $row['status']; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="index.php?edit=<?php echo $row['id']; ?>" class="action-btn btn-mini btn-edit">Edit</a>
                                            <a href="index.php?delete=<?php echo $row['id']; ?>" class="action-btn btn-mini btn-delete" onclick="return confirm('Purge this profile record completely?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 40px 0; font-size: 0.95rem;">No records match your view parameters.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function verifyFormInputs() {
    var name = document.getElementById("student_name").value.trim();
    var roll = document.getElementById("roll_number").value.trim();
    var comp = document.getElementById("company_name").value.trim();
    var role = document.getElementById("intern_role").value.trim();
    
    if (name === "" || roll === "" || comp === "" || role === "") {
        alert("All layout validation input elements must be fully complete.");
        return false;
    }
    return true;
}
</script>
</body>
</html>
<?php $conn->close(); ?>
