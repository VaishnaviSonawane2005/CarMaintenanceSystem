<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: ../auth.php");
    exit();
}
include '../db_connect.php';
include '../toast.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mechanic_id = $_SESSION['id'];

// Handle status update & suggestions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $request_id = (int)$_POST['request_id'];
    $status     = $_POST['status'];
    $suggestion = trim($_POST['suggestion'] ?? '');

    $stmt = $conn->prepare("UPDATE maintenance_requests SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && $status === 'Completed' && $suggestion !== '') {
        $ins = $conn->prepare("INSERT INTO mechanic_suggestions (request_id, mechanic_id, suggestion) VALUES (?, ?, ?)");
        $ins->bind_param("iis", $request_id, $mechanic_id, $suggestion);
        $ins->execute();
        $ins->close();
    }

    $self = basename($_SERVER['PHP_SELF']);
    header("Location: $self?toast=" . ($ok ? 'status_updated' : 'status_error') . "&completed=" . ($status==='Completed'?1:0));
    exit();
}

// Fetch assigned requests
$query = "
    SELECT mr.id, mr.description, mr.preferred_date, mr.preferred_time,
           u.name AS user_name, u.contact AS user_contact, mr.status
    FROM maintenance_requests mr
    JOIN users u ON mr.user_id = u.id
    WHERE mr.mechanic_id = ?
    ORDER BY mr.preferred_date DESC, mr.preferred_time DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Define only the stages from Accepted onwards
$stages = ['Accepted','In Progress','Testing','Completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mechanic Dashboard - Assigned Tasks</title>
<link rel="stylesheet" href="../toast_styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous"/>
<script src="../toast_script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<style>
/* ========== RESET & LAYOUT ========== */
* { box-sizing: border-box; }
body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f6f9; transition: background 0.3s, color 0.3s; }
body.dark { background: #121212; color: #e0e0e0; }
.sidebar { width: 250px; position: fixed; top: 0; left: -250px; height: 100%; background: #2c3e50; transition: 0.3s; }
.sidebar.active { left: 0; }
.main-content { margin-left: 0; padding: 30px; transition: margin-left 0.3s; }
.sidebar.active ~ .main-content { margin-left: 250px; }

/* ========== HEADER ========== */
header { background: #34495e; color: #fff; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
#toggleBtn, #darkModeBtn { background: #17a2b8; color: #fff; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; transition: background 0.3s; }
#toggleBtn:hover, #darkModeBtn:hover { background: #1abc9c; }
header h1 { font-size: 1.8rem; margin-left: 10px; }

/* ========== TASK CARDS ========== */
.task-card { background: inherit; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; animation: fadeIn 0.5s ease; }
body.dark .task-card { box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
.task-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.task-card .header { font-size: 1.4em; color: #2c3e50; margin-bottom: 10px; }
body.dark .task-card .header { color: #e0e0e0; }
.task-card .details { color: #555; margin-bottom: 8px; }
body.dark .task-card .details { color: #ccc; }
.task-card .date-time { color: #888; font-size: 0.9em; margin-bottom: 15px; }
body.dark .task-card .date-time { color: #aaa; }

/* ========== PROGRESS BAR ========== */
.progress-bar { width: 100%; height: 12px; background: #e0e0e0; border-radius: 6px; overflow: hidden; margin-bottom: 20px; }
body.dark .progress-bar { background: #333; }
.progress-bar-fill { height: 100%; background: linear-gradient(135deg, #00c851, #007e33); width: 0; transition: width 0.8s ease; }

/* ========== STEPPER ========== */
.stepper { display: flex; justify-content: space-between; position: relative; margin: 20px 0; }
.stepper::before { content: ''; position: absolute; top: 50%; left: 5%; right: 5%; height: 4px; background: #ddd; transform: translateY(-50%); z-index: 0; }
.step { position: relative; z-index: 1; width: 18%; text-align: center; display: flex; flex-direction: column; align-items: center; }
.circle { width: 40px; height: 40px; border-radius: 50%; background: #ddd; color: #fff; font-weight: bold; display: flex; justify-content: center; align-items: center; transition: background 0.4s, box-shadow 0.4s; }
.step.active .circle { background: #0072ff; box-shadow: 0 0 8px rgba(0,114,255,0.6); }
.step.completed .circle { background: #00c851; box-shadow: 0 0 8px rgba(0,200,81,0.6); }
.label { margin-top: 6px; font-size: 12px; color: #555; text-align: center; }
.step.active .label, .step.completed .label { color: #000; font-weight: 600; }
body.dark .label { color: #aaa; }
body.dark .step.active .label, body.dark .step.completed .label { color: #fff; }

/* ========== BUTTONS ========== */
.action-btn { background: linear-gradient(135deg, #00c851, #007e33); color: #fff; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: transform 0.2s, opacity 0.2s; }
.action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.action-btn:hover:not(:disabled) { transform: scale(1.05); }

/* ========== TOAST & CONFETTI ========== */
#toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }
</style>
</head>
<body>
<?php include '../sidebar.php'; ?>

<div id="toast-container"></div>
<audio id="toast-success" src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" preload="auto"></audio>
<audio id="toast-error" src="https://assets.mixkit.co/sfx/preview/mixkit-wrong-answer-fail-notification-946.mp3" preload="auto"></audio>

<div class="main-content">
  <header>
    <div style="display:flex;align-items:center;">
      <button id="toggleBtn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
      <h1>Assigned Maintenance Requests</h1>
    </div>
    <button id="darkModeBtn" onclick="toggleDarkMode()"><i class="fa fa-moon"></i></button>
  </header>

  <?php while($row = mysqli_fetch_assoc($result)):
      $current = array_search($row['status'], $stages);
      $percent = round((($current)/(count($stages)-1))*100);
  ?>
  <div class="task-card">
    <div class="header">Request #<?= $row['id'] ?> &#8211; <?= htmlspecialchars($row['user_name']) ?></div>
    <div class="details"><?= htmlspecialchars($row['description']) ?></div>
    <div class="date-time">Date: <?= $row['preferred_date'] ?> | Time: <?= $row['preferred_time'] ?></div>

    <div class="progress-bar">
      <div class="progress-bar-fill" style="width:<?= $percent ?>%;"></div>
    </div>

    <div class="stepper">
      <?php foreach ($stages as $i => $s):
          $cls = ($i<$current)?'completed':(($i===$current)?'active':'');
      ?>
      <div class="step <?= $cls ?>">
        <div class="circle">
          <?php if($cls==='completed'): ?>
            <i class="fa fa-check"></i>
          <?php elseif($cls==='active'): ?>
            <i class="fa fa-spinner fa-pulse"></i>
          <?php else: ?>
            <?= $i+1 ?>
          <?php endif; ?>
        </div>
        <div class="label"><?= $s ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if($current < count($stages)-1): $next = $stages[$current+1]; ?>
    <button class="action-btn" onclick="advance(<?= $row['id'] ?>, '<?= $next ?>', this)">
      <?= $next==='Completed'? 'Finish & Suggest' : 'Mark ' . $next ?>
    </button>
    <?php endif; ?>
  </div>
  <?php endwhile; ?>
</div>

<script>
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('active');
  document.querySelector('.main-content').classList.toggle('active');
}
function toggleDarkMode() {
  document.body.classList.toggle('dark');
}
function advance(id, status, btn) {
  btn.disabled = true;
  let suggestion = '';
  if (status==='Completed') {
    suggestion = prompt('Enter suggestions for future maintenance:');
    if (suggestion === null) { btn.disabled = false; return; }
    confetti({ particleCount: 100, spread: 70 });
  }
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = window.location.pathname;
  [['request_id', id], ['status', status], ['suggestion', suggestion]].forEach(([n, v])=>{
    const i = document.createElement('input');
    i.type = 'hidden'; i.name = n; i.value = v;
    form.appendChild(i);
  });
  document.body.appendChild(form);
  form.submit();
}
window.onload = function() {
  const p = new URLSearchParams(window.location.search);
  const toastType = p.get('toast'), completed = p.get('completed');
  if (toastType==='status_updated') {
    createToast('success','Progress updated!',3000);
    if (completed==='1') confetti({ particleCount:100, spread:70 });
  }
  if (toastType==='status_error') createToast('error','Update failed!',3000);
};
</script>
</body>
</html>
