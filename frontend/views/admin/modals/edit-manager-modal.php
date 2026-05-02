<!-- Edit Manager Modal -->
<div id="editManagerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Edit Manager</h2>
                <p>Update manager contact information.</p>
            </div>
            <button class="modal-close" onclick="closeEditManagerModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form action="/foodbank/backend/controllers/admin/foodbanks/process_edit_manager.php" method="POST">
                <input type="hidden" name="foodbank_id" id="em-foodbank-id">

                <h3>Manager Information</h3>
                <div class="form-row">
                    <input type="text" name="manager_first_name" id="em-first-name" placeholder="First Name" required>
                    <input type="text" name="manager_last_name" id="em-last-name" placeholder="Last Name" required>
                </div>
                <input type="email" name="manager_email" id="em-email" placeholder="Email Address" required>
                <input type="text" name="manager_phone" id="em-phone" placeholder="Phone Number" required>
                <textarea name="manager_address" id="em-address" placeholder="Address" required></textarea>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditManagerModal()">Cancel</button>
                    <button type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>