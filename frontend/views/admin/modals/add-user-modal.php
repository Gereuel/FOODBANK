<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Add New User</h2>
                <p>Fill in the details to create a new account.</p>
            </div>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>

        <div class="modal-body">
            <form action="/foodbank/backend/controllers/process_add_user.php" method="POST">

                <h3>Account Details</h3>
                <select name="account_type" required>
                    <option value="" disabled selected>Select Account Type...</option>
                    <option value="PA">Personal Account (Donor)</option>
                    <option value="AA">System Administrator</option>
                </select>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Temporary Password" required>
                <input type="text" name="phone_number" placeholder="Phone Number" required>

                <h3>Personal Information</h3>
                <div class="form-row">
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="middle_name" placeholder="Middle Name (Optional)">
                </div>
                <div class="form-row">
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="text" name="suffix" placeholder="Suffix (Optional)">
                </div>
                <textarea name="address" placeholder="Full Address" required></textarea>
                <label>Birthdate</label>
                <input type="date" name="birthdate" required>

                <div class="modal-footer">
                    <button type="button" onclick="closeAddModal()">Cancel</button>
                    <button type="submit">Create User Account</button>
                </div>

            </form>
        </div>

    </div>
</div>