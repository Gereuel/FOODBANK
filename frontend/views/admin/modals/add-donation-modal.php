<?php
/**
 * @var array $donors    Injected from donations.php
 * @var array $foodbanks Injected from donations.php
 */
?>

<!-- Add Donation Modal -->
<div id="addDonationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Add Donation Report</h2>
                <p>Log a new donation record manually.</p>
            </div>
            <button class="modal-close" onclick="closeAddDonationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form action="/foodbank/backend/controllers/admin/donations/process_add_donations.php" method="POST" enctype="multipart/form-data">

                <h3>Donor</h3>
                <select name="donor_account_id" required>
                    <option value="" disabled selected>Select Donor...</option>
                    <?php foreach ($donors as $donor): ?>
                    <option value="<?= $donor['Account_ID'] ?>">
                        <?= htmlspecialchars($donor['First_Name'] . ' ' . $donor['Last_Name']) ?> — <?= htmlspecialchars($donor['Custom_App_ID']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <h3>Donation Details</h3>
                <select name="item_type" required>
                    <option value="" disabled selected>Select Item Type...</option>
                    <option value="Food Items">Food Items</option>
                    <option value="Clothing">Clothing</option>
                    <option value="Cash Donation">Cash Donation</option>
                    <option value="Medicine">Medicine</option>
                    <option value="Perishable Goods">Perishable Goods</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" name="item_description" placeholder="Item Description (Optional)">
                <input type="text" name="quantity_description" placeholder="Quantity (e.g. 100k Items, $1 Million)" required>
                <textarea name="pickup_address" placeholder="Pickup / Drop-off Address" required></textarea>

                <div class="form-row">
                    <div>
                        <label>Donation Date</label>
                        <input type="date" name="date_donated" required>
                    </div>
                    <div>
                        <label>Donation Time</label>
                        <input type="time" name="donation_time" required>
                    </div>
                </div>

                <h3>Food Bank</h3>
                <select name="foodbank_id" required>
                    <option value="" disabled selected>Select Food Bank...</option>
                    <?php foreach ($foodbanks as $fb): ?>
                    <option value="<?= $fb['FoodBank_ID'] ?>">
                        <?= htmlspecialchars($fb['Organization_Name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <h3>Status & Proof</h3>
                <select name="status" required>
                    <option value="" disabled selected>Select Status...</option>
                    <option value="Pending">Pending</option>
                    <option value="In Transit">In Transit</option>
                    <option value="Received">Received</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <label>Proof of Delivery (Optional)</label>
                <input type="file" name="proof_of_delivery" accept="image/*,.pdf">

                <textarea name="notes" placeholder="Additional Notes (Optional)"></textarea>

                <div class="modal-footer">
                    <button type="button" onclick="closeAddDonationModal()">Cancel</button>
                    <button type="submit">Save Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
