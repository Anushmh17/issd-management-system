<?php
// =====================================================
// ISSD Management - Admin: Unified Payment Management Hub
// admin/payments/index.php
// =====================================================
define('PAGE_TITLE', 'Finance Hub');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'status');
$result  = getPaymentsList($pdo, $filters, $page, 15);
$payments = $result['payments'];
$total = $result['total'];
$pages = $result['pages'];

$stats = getFinancialStats($pdo);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<style>
  /* --- PREMIUM BORDER REINFORCEMENT (FORCE APPLIED) --- */
  body.lms-dark-mode .card-lms,
  body.lms-dark-mode .stat-card,
  body.lms-dark-mode .bento-card {
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4), 0 0 15px rgba(255, 255, 255, 0.03) !important;
    border-radius: 24px !important;
    background: rgba(30, 41, 59, 0.5) !important;
    backdrop-filter: blur(20px) !important;
  }
</style>

<style>
@keyframes pulse-highlight {
  0% { background-color: rgba(91, 78, 250, 0.1); }
  50% { background-color: rgba(91, 78, 250, 0.25); }
  100% { background-color: rgba(91, 78, 250, 0.1); }
}
.row-highlight {
  animation: pulse-highlight 2s infinite;
  border-left: 5px solid var(--primary) !important;
}

@media (max-width: 768px) {
  #page-content { padding: 15px 15px !important; overflow-x: hidden !important; width: 100% !important; }
  /* Stack page header: title above buttons */
  .page-header {
    padding: 0 !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 10px !important;
  }
  .page-header .d-flex.gap-2 {
    width: 100% !important;
    flex-direction: column !important;
    gap: 8px !important;
  }
  .page-header .d-flex.gap-2 a, .page-header .d-flex.gap-2 button {
    width: 100% !important;
    text-align: center !important;
    justify-content: center !important;
  }

  /* Stat cards: 2x2 grid */
  .row.g-3 { margin-left: -5px !important; margin-right: -5px !important; }
  .row.g-3 > .col-md-3 {
    flex: 0 0 50% !important;
    max-width: 50% !important;
    padding-left: 5px !important;
    padding-right: 5px !important;
  }
  .stat-card {
    padding: 14px 12px !important;
  }
  .stat-card .stat-icon {
    width: 36px !important;
    height: 36px !important;
    font-size: 16px !important;
  }
  .stat-card .stat-value {
    font-size: 18px !important;
  }
  .stat-card .stat-label {
    font-size: 10px !important;
  }

  /* Table Attachment & Compacting */
  .card-lms { margin: 0 -10px !important; border-radius: 0 !important; border-left: none !important; border-right: none !important; }
  .card-lms-header { padding: 15px 20px !important; gap: 10px !important; }
  .list-legend-title { font-size: 18px !important; }
  .list-legend-label { font-size: 8px !important; }
  .search-bar { min-width: 100% !important; }
  .search-bar input { padding: 8px 0 !important; font-size: 13px !important; }
  .card-lms-header form select { width: 100% !important; height: 38px !important; font-size: 12px !important; }
  .card-lms-header form button { height: 38px !important; width: 38px !important; }

  .card-lms-body { padding: 0 !important; overflow-x: hidden !important; }

  /* Column Visibility */
  .table-lms th:nth-child(1), .table-lms td:nth-child(1),
  .table-lms th:nth-child(3), .table-lms td:nth-child(3),
  .table-lms th:nth-child(5), .table-lms td:nth-child(5) { display: none !important; }

  .table-lms th:nth-child(2), .table-lms td:nth-child(2) { width: auto !important; }
  .table-lms th:nth-child(4), .table-lms td:nth-child(4) { width: 90px !important; }
  .table-lms th:nth-child(6), .table-lms td:nth-child(6) { width: 80px !important; }
  .table-lms th:nth-child(7), .table-lms td:nth-child(7) { width: 60px !important; text-align: center !important; }
  /* Column 8: Actions */
  .table-lms th:nth-child(8), .table-lms td:nth-child(8) { 
    width: 45px !important; 
    padding-right: 12px !important;
    text-align: right !important;
    display: table-cell !important;
  }
  .table-lms td:nth-child(8) {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
  }
  .btn-action-dots {
    width: 30px !important; height: 30px !important; border-radius: 8px !important;
    background: rgba(91, 78, 250, 0.1) !important; color: var(--primary) !important;
    display: flex !important; align-items: center; justify-content: center; border: none !important;
  }
}
</style>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Financial Management Hub</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Finance</span></div>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-light rounded-pill px-4 fw-800 shadow-sm no-print"><i class="fas fa-print me-2"></i>Print Report</button>
        <a href="alerts.php" class="btn btn-warning rounded-pill px-4 fw-800 shadow-sm"><i class="fas fa-bell me-2"></i>Alerts</a>
        <a href="add.php" class="btn-primary-grad px-4"><i class="fas fa-plus"></i> New Payment</a>
    </div>
  </div>

  <!-- Real-time Stats Ticker -->
  <div class="row g-3 mb-25">
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--accent);">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-body">
                <div class="stat-value">Rs. <?= number_format($stats['monthly_income'], 0) ?></div>
                <div class="stat-label">Income (<?= date('M') ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--danger);">
            <div class="stat-icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="stat-body">
                <div class="stat-value">Rs. <?= number_format($stats['total_outstanding'], 0) ?></div>
                <div class="stat-label">Outstanding</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--info);">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Receipts</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--warning);">
            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= count(array_filter($payments, function($p){ return $p['status']=='overdue'; })) ?></div>
                <div class="stat-label">Urgent Issues</div>
            </div>
        </div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="list-legend">
            <div class="list-legend-label">Transaction Records</div>
            <div class="list-legend-title">Payment History</div>
        </div>

        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width: 600px;">
            <div class="search-bar flex-grow-1" style="border-radius:12px; padding:0 15px; display:flex; align-items:center;">
                <i class="fas fa-search text-muted me-2"></i>
                <input type="text" name="search" placeholder="Search Student name or course..." class="border-0 bg-transparent py-2 w-100" value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="form-select border-0" style="width:140px; border-radius:12px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="paid"    <?= $status==='paid'?'selected':'' ?>>Paid</option>
                <option value="partial" <?= $status==='partial'?'selected':'' ?>>Partial</option>
                <option value="overdue" <?= $status==='overdue'?'selected':'' ?>>Overdue</option>
            </select>
            <button type="submit" class="btn-primary-grad btn-sm px-3" style="height: 42px;"><i class="fas fa-filter"></i></button>
        </form>
    </div>

    <div class="card-lms-body p-0 overflow-x-auto">
        <?php if (empty($payments)): ?>
            <div class="empty-state p-5 text-center">
                <i class="fas fa-receipt fa-4x mb-3 opacity-20"></i>
                <p class="text-muted">No transaction history found.</p>
            </div>
        <?php else: ?>
            <table class="table-lms mb-0">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Student Details</th>
                        <th>Course / Program</th>
                        <th>Amount Paid</th>
                        <th>Balance Due</th>
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="d-table-cell d-md-none" style="text-align:center;">Status</th>
                        <th style="text-align:center;" class="d-none d-md-table-cell">Actions</th>
                        <th class="d-table-cell d-md-none" style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $highlightId = (int)($_GET['highlight_id'] ?? 0);
                    foreach ($payments as $p): 
                        $isHighlighted = ($highlightId === (int)$p['id']);
                    ?>
                    <tr class="<?= $isHighlighted ? 'row-highlight' : '' ?>" id="payment-<?= $p['id'] ?>">
                        <td>
                            <div class="fw-800 text-muted" style="font-size:11px;">#RCPT-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></div>
                            <div style="font-size:10px;"><?= date('d M Y', strtotime($p['payment_date'])) ?></div>
                        </td>
                        <td>
                            <div class="fw-700"><?= htmlspecialchars($p['full_name']) ?></div>
                            <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($p['student_reg']) ?></div>
                        </td>
                        <td>
                            <div class="fw-600" style="font-size:12px;"><?= htmlspecialchars($p['course_code']) ?></div>
                            <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars($p['course_name']) ?></div>
                        </td>
                        <td class="fw-800 text-success">Rs. <?= number_format($p['amount_paid'], 2) ?></td>
                        <td class="fw-800 text-danger">Rs. <?= number_format($p['balance'], 2) ?></td>
                        <td class="d-none d-md-table-cell">
                            <?php 
                            $b_class = $p['status'] == 'paid' ? 'accent' : ($p['status'] == 'partial' ? 'warning' : 'danger');
                            ?>
                            <span class="badge-lms" style="background:var(--<?= $b_class ?>-light); color:var(--<?= $b_class ?>-dark); text-transform:uppercase; font-size:10px;">
                                <?= $p['status'] ?>
                            </span>
                        </td>
                        <td class="d-table-cell d-md-none" style="text-align:center;">
                             <?php 
                             $b_class = $p['status'] == 'paid' ? 'accent' : ($p['status'] == 'partial' ? 'warning' : 'danger');
                             ?>
                             <span class="badge-lms" style="background:var(--<?= $b_class ?>-light); color:var(--<?= $b_class ?>-dark); padding: 4px 8px; font-size:9px;">
                                 <?= strtoupper(substr($p['status'], 0, 1)) ?>
                             </span>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="d-flex gap-2">
                                <a href="receipt.php?id=<?= $p['id'] ?>" class="btn-lms btn-sm" title="Print Receipt"><i class="fas fa-print"></i></a>
                                <a href="details.php?student_id=<?= $p['student_id'] ?>" class="btn-lms btn-sm btn-outline" title="View Student History"><i class="fas fa-eye"></i></a>
                            </div>
                        </td>
                        <td class="d-table-cell d-md-none" style="text-align:center;">
                          <?php $pJson = json_encode(['id'=>$p['id'],'name'=>$p['full_name'],'reg'=>$p['student_reg'],'course'=>$p['course_name'],'code'=>$p['course_code'],'paid'=>'Rs. '.number_format($p['amount_paid'],2),'balance'=>'Rs. '.number_format($p['balance'],2),'date'=>date('d M Y',strtotime($p['payment_date'])),'status'=>$p['status']]); ?>
                          <button class="btn-action-dots" onclick="openPaymentMenu(<?= htmlspecialchars($pJson) ?>, event)">
                            <i class="fas fa-ellipsis-vertical"></i>
                          </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="card-footer p-3">
        <div class="pagination-lms justify-content-center">
            <?php for ($i=1; $i<=$pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Payment Actions & Details Modal -->
<div class="modal fade" id="paymentActionsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #f8fafc;">
      <div class="modal-header" style="background: var(--grad-primary); border: none; padding: 25px; color: #fff; position: relative;">
        <div style="position: relative; z-index: 2;">
          <h5 class="modal-title fw-800 mb-0" id="modalStudentName">Student Name</h5>
          <div id="modalReceiptID" style="font-size: 13px; opacity: 0.9; font-weight: 500;">Receipt #00000</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 25px; right: 25px; z-index: 3;"></button>
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%); z-index: 1;"></div>
      </div>
      <div class="modal-body p-4">
          <div id="paymentDetailView" class="animate__animated animate__fadeIn">
             <div class="info-grid-lms" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: rgba(0,0,0,0.03); padding: 14px; border-radius: 15px; margin-bottom: 12px;">
                <div class="info-item">
                  <label style="font-size: 9px; opacity: 0.6; text-transform: uppercase; display: block; margin-bottom: 2px;">Paid Amount</label>
                  <span id="infoPaid" style="font-size: 13px; font-weight: 700; color: #059669;">-</span>
                </div>
                <div class="info-item">
                  <label style="font-size: 9px; opacity: 0.6; text-transform: uppercase; display: block; margin-bottom: 2px;">Balance Due</label>
                  <span id="infoBalance" style="font-size: 13px; font-weight: 700; color: #ef4444;">-</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                  <label style="font-size: 9px; opacity: 0.6; text-transform: uppercase; display: block; margin-bottom: 2px;">Course</label>
                  <span id="infoCourse" style="font-size: 13px; font-weight: 700; color: var(--dark);">-</span>
                </div>
                <div class="info-item">
                  <label style="font-size: 9px; opacity: 0.6; text-transform: uppercase; display: block; margin-bottom: 2px;">Date</label>
                  <span id="infoDate" style="font-size: 13px; font-weight: 700;">-</span>
                </div>
                <div class="info-item">
                  <label style="font-size: 9px; opacity: 0.6; text-transform: uppercase; display: block; margin-bottom: 2px;">Status</label>
                  <span id="infoStatus" style="font-size: 13px; font-weight: 700;">-</span>
                </div>
             </div>
             <div class="d-flex flex-column gap-2">
               <button class="action-menu-item btn-primary-grad text-white" onclick="toggleDetailView(false)" style="background: var(--grad-primary) !important; color: white !important; width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); font-size: 14px; font-weight: 600;">
                  <i class="fas fa-list-check" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); border-radius: 8px;"></i> Manage & Actions
               </button>
               <button class="action-menu-item" data-bs-dismiss="modal" style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); background: #fff; font-size: 14px; font-weight: 600;">
                  <i class="fas fa-times" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.05); border-radius: 8px;"></i> Close Details
               </button>
             </div>
          </div>

          <div id="paymentActionList" class="action-menu-list" style="display: none;">
            <button class="action-menu-item" onclick="toggleDetailView(true)" style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); background: #fff; font-size: 14px; font-weight: 600; margin-bottom: 8px;">
              <i class="fas fa-arrow-left" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(91,78,250,0.1); color: var(--primary); border-radius: 8px;"></i>
              <div style="text-align: left;">
                Back to Details
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">View payment info</div>
              </div>
            </button>
            <a href="#" id="actionReceipt" class="action-menu-item" style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); background: #fff; text-decoration: none; color: inherit; margin-bottom: 8px; font-size: 14px; font-weight: 600;">
              <i class="fas fa-print" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(91,78,250,0.1); color: var(--primary); border-radius: 8px;"></i>
              <div style="text-align: left;">
                Print Receipt
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Download PDF</div>
              </div>
            </a>
            <a href="#" id="actionHistory" class="action-menu-item" style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); background: #fff; text-decoration: none; color: inherit; margin-bottom: 8px; font-size: 14px; font-weight: 600;">
              <i class="fas fa-history" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(91,78,250,0.1); color: var(--primary); border-radius: 8px;"></i>
              <div style="text-align: left;">
                View Student History
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">See all payments</div>
              </div>
            </a>
          </div>
      </div>
    </div>
  </div>
</div>

<script>
function openPaymentMenu(payment, event) {
  if (event.target.closest('a') || event.target.closest('button:not(.btn-action-dots)')) return;
  
  document.getElementById('modalStudentName').textContent = payment.name;
  document.getElementById('modalReceiptID').textContent = 'Receipt #RCPT-' + payment.id.toString().padStart(5, '0');
  
  // Update Details
  document.getElementById('infoPaid').textContent = payment.paid;
  document.getElementById('infoBalance').textContent = payment.balance;
  document.getElementById('infoCourse').textContent = `${payment.code} - ${payment.course}`;
  document.getElementById('infoDate').textContent = payment.date;
  document.getElementById('infoStatus').textContent = payment.status.charAt(0).toUpperCase() + payment.status.slice(1);
  
  // Update Links
  document.getElementById('actionReceipt').href = `receipt.php?id=${payment.id}`;
  document.getElementById('actionHistory').href = `details.php?student_id=${payment.reg}`;
  
  toggleDetailView(true);
  const modal = new bootstrap.Modal(document.getElementById('paymentActionsModal'));
  modal.show();
}

function toggleDetailView(show) {
  document.getElementById('paymentDetailView').style.display = show ? 'block' : 'none';
  document.getElementById('paymentActionList').style.display = show ? 'none' : 'block';
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
