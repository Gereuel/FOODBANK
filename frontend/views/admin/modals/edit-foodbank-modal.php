<!-- Edit Food Bank Modal -->
<div id="editFoodBankModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Edit Food Bank</h2>
                <p>Update food bank details.</p>
            </div>
            <button class="modal-close" onclick="closeEditFoodBankModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form action="/foodbank/backend/controllers/admin/foodbanks/process_edit_foodbank.php" method="POST">
                <input type="hidden" name="foodbank_id" id="efb-id">

                <h3>Organization</h3>
                <input type="text" name="organization_name" id="efb-name" placeholder="Organization Name" required>
                <input type="text" name="physical_address" id="efb-address" placeholder="Physical Address" required>
                <input type="email" name="org_email" id="efb-org-email" placeholder="Organization Email" required>

                <div class="form-row">
                    <div>
                        <label>Verification Status</label>
                        <select name="verification_status" id="efb-verification">
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                    <div>
                        <label>Org Status</label>
                        <select name="org_status" id="efb-org-status">
                            <option value="Pending">Pending</option>
                            <option value="Active">Active</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <h3>Operating Hours</h3>
                <div class="form-row">
                    <div>
                        <label>Opening Time</label>
                        <input type="time" name="time_open" id="efb-time-open" required>
                    </div>
                    <div>
                        <label>Closing Time</label>
                        <input type="time" name="time_close" id="efb-time-close" required>
                    </div>
                </div>
                <label>Operating Days</label>
                <input type="text" name="operating_days" id="efb-days" placeholder="e.g. Mon-Fri" required>

                <h3>Public Contact</h3>
                <input type="email" name="public_email" id="efb-public-email" placeholder="Public Email (Optional)">
                <input type="text" name="public_phone" id="efb-public-phone" placeholder="Public Phone (Optional)">

                <h3>Manager Information</h3>
                <div class="form-row">
                    <input type="text" name="manager_first_name" id="efb-mgr-first" placeholder="First Name">
                    <input type="text" name="manager_last_name" id="efb-mgr-last" placeholder="Last Name">
                </div>
                <input type="email" name="manager_email" id="efb-mgr-email" placeholder="Manager Email">
                <input type="text" name="manager_phone" id="efb-mgr-phone" placeholder="Manager Phone">
                <textarea name="manager_address" id="efb-mgr-address" placeholder="Manager Address"></textarea>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditFoodBankModal()">Cancel</button>
                    <button type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>