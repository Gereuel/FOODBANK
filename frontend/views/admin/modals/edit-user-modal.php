<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Edit User</h2>
                <p>Update the details of this account.</p>
            </div>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>

        <div class="modal-body">
            <form action="/foodbank/backend/controllers/admin/users/process_edit_user.php" method="POST">
                <input type="hidden" name="user_id" id="edit-user-id">
                <input type="hidden" name="account_id" id="edit-account-id">

                <h3>Account Details</h3>
                <select name="account_type" id="edit-account-type" required>
                    <option value="" disabled selected>Select Account Type...</option>
                    <option value="PA">Personal Account (Donor)</option>
                    <option value="FA">Food Bank Manager</option>
                    <option value="AA">System Administrator</option>
                </select>
                <input type="email" name="email" id="edit-email" placeholder="Email Address" required>
                <input type="text" name="phone_number" id="edit-phone" placeholder="Phone Number" required>

                <h3>Personal Information</h3>
                <div class="form-row">
                    <input type="text" name="first_name" id="edit-first-name" placeholder="First Name" required>
                    <input type="text" name="middle_name" id="edit-middle-name" placeholder="Middle Name (Optional)">
                </div>
                <div class="form-row">
                    <input type="text" name="last_name" id="edit-last-name" placeholder="Last Name" required>
                    <input type="text" name="suffix" id="edit-suffix" placeholder="Suffix (Optional)">
                </div>
                <textarea name="address" id="edit-address" placeholder="Full Address" required></textarea>
                <label>Birthdate</label>
                <input type="date" name="birthdate" id="edit-birthdate" required>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()">Cancel</button>
                    <button type="submit">Save Changes</button>
                </div>

            </form>
        </div>

    </div>
</div>