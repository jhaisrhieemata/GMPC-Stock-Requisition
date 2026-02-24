<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'calendar';
$pageTitle = 'Calendar - GMPC Stock Requisition';

$selectedBranch = $_GET['branch'] ?? 'all';
$selectedMonth = $_GET['month'] ?? date('Y-m');

$branches = $conn->query("SELECT id, name FROM branches ORDER BY name");

$query = "SELECT r.id, r.requisition_code, r.request_type, r.status, r.created_at, b.name as branch_name 
          FROM requisitions r 
          LEFT JOIN branches b ON r.branch_id = b.id 
          WHERE 1=1";
if ($selectedBranch !== 'all') {
    $query .= " AND r.branch_id = " . (int)$selectedBranch;
}
$query .= " UNION ALL 
           SELECT sr.id, sr.request_code, 'Special Request', sr.status, sr.created_at, b.name as branch_name 
           FROM special_requests sr 
           LEFT JOIN branches b ON sr.branch_id = b.id 
           WHERE 1=1";
if ($selectedBranch !== 'all') {
    $query .= " AND sr.branch_id = " . (int)$selectedBranch;
}
$query .= " ORDER BY created_at DESC";

$requests = $conn->query($query);

$requestDates = [];
$requestsByDate = [];
while ($row = $requests->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['created_at']));
    $requestDates[] = $date;
    if (!isset($requestsByDate[$date])) {
        $requestsByDate[$date] = [];
    }
    $requestsByDate[$date][] = $row;
}
$requestDates = array_unique($requestDates);

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-calendar3 me-2"></i>Request Calendar</h4>
        <form method="GET" class="d-flex gap-2">
            <select name="branch" class="form-select" style="width: 200px;" onchange="this.form.submit()">
                <option value="all">All Branches</option>
                <?php while ($branch = $branches->fetch_assoc()): ?>
                    <option value="<?= $branch['id'] ?>" <?= $selectedBranch == $branch['id'] ? 'selected' : '' ?>>
                        <?= $branch['name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="month" name="month" class="form-control" style="width: 160px;" value="<?= $selectedMonth ?>" onchange="this.form.submit()">
        </form>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button class="btn btn-outline-primary btn-sm" onclick="changeMonth(-1)">
                            <i class="bi bi-chevron-left"></i> Previous
                        </button>
                        <h5 class="mb-0" id="calendarTitle"></h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="changeMonth(1)">
                            Next <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <div class="calendar-grid">
                        <div class="row text-center fw-bold mb-2">
                            <div class="col text-danger">Sun</div>
                            <div class="col">Mon</div>
                            <div class="col">Tue</div>
                            <div class="col">Wed</div>
                            <div class="col">Thu</div>
                            <div class="col">Fri</div>
                            <div class="col text-primary">Sat</div>
                        </div>
                        <div id="calendarDays" class="row"></div>
                    </div>
                    <div class="mt-3 d-flex gap-3 justify-content-center">
                        <span class="badge bg-warning"><i class="bi bi-circle-fill me-1"></i>Has Request</span>
                        <span class="badge bg-primary"><i class="bi bi-circle-fill me-1"></i>Today</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Requests on <span id="selectedDateText">Today</span></h6>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <div id="requestsList" class="list-group list-group-flush">
                        <div class="list-group-item text-center text-muted py-4">
                            Select a date to view requests
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = new Date('<?= $selectedMonth ?>-01');
const requestDates = <?= json_encode($requestDates) ?>;
const requestsByDate = <?= json_encode($requestsByDate) ?>;
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const today = new Date();
    
    document.getElementById('calendarTitle').textContent = monthNames[month] + ' ' + year;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    let html = '';
    let day = 1;
    
    for (let i = 0; i < 6; i++) {
        html += '<div class="row">';
        for (let j = 0; j < 7; j++) {
            if ((i === 0 && j < firstDay) || day > daysInMonth) {
                html += '<div class="col text-center p-2"></div>';
            } else {
                const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                const hasRequest = requestDates.includes(dateStr);
                const isToday = today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;
                const hasMany = requestsByDate[dateStr] && requestsByDate[dateStr].length > 1;
                
                let classes = 'col text-center p-2';
                let style = 'cursor: pointer; border-radius: 8px;';
                if (hasRequest) {
                    style += ' background-color: #ffc107; color: #000;';
                } else {
                    style += ' background-color: #f8f9fa;';
                }
                if (isToday) {
                    style += ' border: 2px solid #0d6efd;';
                }
                
                html += `<div class="${classes}" style="${style}" onclick="showRequests('${dateStr}')">
                    <span class="fw-semibold">${day}</span>
                    ${hasMany ? '<span class="badge bg-dark rounded-pill ms-1">' + requestsByDate[dateStr].length + '</span>' : ''}
                </div>`;
                day++;
            }
        }
        html += '</div>';
        if (day > daysInMonth) break;
    }
    
    document.getElementById('calendarDays').innerHTML = html;
}

function changeMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    const url = new URL(window.location.href);
    url.searchParams.set('month', currentDate.getFullYear() + '-' + String(currentDate.getMonth() + 1).padStart(2, '0'));
    window.location.href = url.toString();
}

function showRequests(dateStr) {
    document.getElementById('selectedDateText').textContent = new Date(dateStr).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    const requests = requestsByDate[dateStr] || [];
    const listEl = document.getElementById('requestsList');
    
    if (requests.length === 0) {
        listEl.innerHTML = '<div class="list-group-item text-center text-muted py-4">No requests on this date</div>';
        return;
    }
    
    let html = '';
    requests.forEach(req => {
        let statusClass = 'bg-secondary';
        if (req.status === 'Pending') statusClass = 'bg-warning text-dark';
        else if (req.status === 'Approved') statusClass = 'bg-success';
        else if (req.status === 'Rejected') statusClass = 'bg-danger';
        else if (req.status === 'Completed') statusClass = 'bg-primary';
        
        html += `<div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1">${req.requisition_code}</h6>
                    <small class="text-muted">${req.request_type} - ${req.branch_name || 'N/A'}</small>
                </div>
                <span class="badge ${statusClass}">${req.status}</span>
            </div>
        </div>`;
    });
    listEl.innerHTML = html;
}

renderCalendar();
</script>

<?php include '../includes/footer.php'; ?>
