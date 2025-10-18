<?php
// top.php में session, db connection आदि पहले से हैं
include('top.php');

// Payout को पूरा करने के बाद मैसेज दिखाने के लिए
$success_msg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);

// --- सभी पेंडिंग Payout रिक्वेस्ट्स को Fetch करें ---
$sql = "SELECT p.*, d.name as delivery_boy_name, d.mobile, d.ac_holder_name, d.ac_no, d.ifsc_code, d.upi_id
        FROM delivery_payouts p
        JOIN delivery_boy d ON p.delivery_boy_id = d.id
        WHERE p.status = 'pending'
        ORDER BY p.request_date ASC";
$res = mysqli_query($con, $sql);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Pending Payout Requests</h4>
                
                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Delivery Boy</th>
                                <th>Amount</th>
                                <th>Request Date</th>
                                <th>Bank Account Details</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($res) > 0):
                                while ($row = mysqli_fetch_assoc($res)):
                            ?>
                                <tr>
                                    <td>
                                        <p class="mb-0"><b><?php echo htmlspecialchars($row['delivery_boy_name']); ?></b></p>
                                        <p class="text-muted"><?php echo htmlspecialchars($row['mobile']); ?></p>
                                    </td>
                                    <td><b>₹<?php echo number_format($row['amount'], 2); ?></b></td>
                                    <td><?php echo date('d M, Y', strtotime($row['request_date'])); ?></td>
                                    <td>
                                        <p class="mb-0"><b>Name:</b> <?php echo htmlspecialchars($row['ac_holder_name']); ?></p>
                                        <p class="mb-0"><b>A/C:</b> <?php echo htmlspecialchars($row['ac_no']); ?></p>
                                        <p class="mb-0"><b>IFSC:</b> <?php echo htmlspecialchars($row['ifsc_code']); ?></p>
                                        <?php if($row['upi_id']): ?>
                                            <p class="mb-0"><b>UPI:</b> <?php echo htmlspecialchars($row['upi_id']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#payoutModal" 
                                                data-payout-id="<?php echo $row['id']; ?>" 
                                                data-amount="<?php echo number_format($row['amount'], 2); ?>"
                                                data-name="<?php echo htmlspecialchars($row['delivery_boy_name']); ?>">
                                            Mark as Paid
                                        </button>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No pending payout requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payoutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="complete_payout.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Payout</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>You are paying <b>₹<span id="modal_amount"></span></b> to <b id="modal_name"></b>.</p>
                    <input type="hidden" name="payout_id" id="modal_payout_id">
                    <div class="form-group">
                        <label for="transaction_details">Transaction ID / Details*</label>
                        <textarea name="transaction_details" class="form-control" rows="3" placeholder="Enter transaction ID, payment mode, or any other details here." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm & Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
// Pass data to the modal
$('#payoutModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var payoutId = button.data('payout-id');
  var amount = button.data('amount');
  var name = button.data('name');
  
  var modal = $(this);
  modal.find('#modal_payout_id').val(payoutId);
  modal.find('#modal_amount').text(amount);
  modal.find('#modal_name').text(name);
});
</script>