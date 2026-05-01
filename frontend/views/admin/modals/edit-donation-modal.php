<!-- Edit Donation Modal -->
<div id="editDonationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Edit Donation</h2>
                <p>Update donation status and details.</p>
            </div>
            <button class="modal-close" onclick="closeEditDonationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form action="/foodbank/backend/controllers/admin/donations/process_edit_donation.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="donation_id" id="edit-donation-id">

                <h3>Status</h3>
                <select name="status" id="edit-donation-status" required>
                    <option value="Pending">Pending</option>
                    <option value="In Transit">In Transit</option>
                    <option value="Received">Received</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <h3>Quantity</h3>
                <input type="text" name="quantity_description" id="edit-donation-quantity" placeholder="Quantity" required>

                <h3>Proof of Delivery</h3>
                <input type="file" name="proof_of_delivery" accept="image/*,.pdf">

                <h3>Notes</h3>
                <textarea name="notes" id="edit-donation-notes" placeholder="Additional Notes"></textarea>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditDonationModal()">Cancel</button>
                    <button type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>