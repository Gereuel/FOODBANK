<!-- Add Food Bank Modal — 4 Step -->
<div id="addFoodBankModal" class="modal">
    <div class="modal-content modal-content--lg">

        <!-- Green Header -->
        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Register New Food Bank</h2>
                <p>Complete all required information to register your food bank.</p>
            </div>
            <button class="modal-close" onclick="closeAddFoodBankModal()">&times;</button>
        </div>

        <!-- Step Indicator -->
        <div class="stepper">
            <div class="step active" id="step-indicator-1">
                <div class="step-circle">1</div>
                <div class="step-label">Organization</div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-indicator-2">
                <div class="step-circle">2</div>
                <div class="step-label">Legal Documents</div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-indicator-3">
                <div class="step-circle">3</div>
                <div class="step-label">Contact Information</div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-indicator-4">
                <div class="step-circle">4</div>
                <div class="step-label">Operations</div>
            </div>
        </div>

        <div class="modal-body">
            <form id="addFoodBankForm"
                action="/foodbank/backend/controllers/admin/foodbanks/process_add_foodbank.php"
                method="POST"
                enctype="multipart/form-data">

                <!-- Step 1: Organization -->
                <div class="form-step" id="form-step-1">
                    <h3>Organization Details</h3>
                    <input type="text" name="organization_name" placeholder="Organization Name" required>
                    <input type="text" name="physical_address" placeholder="Physical Address" required>
                    <input type="email" name="org_email" placeholder="Organization Email" required>
                    <input type="password" name="org_password" placeholder="Set Organization Password" required>
                    <input type="password" name="org_password_confirm" placeholder="Confirm Password" required>
                </div>

                <!-- Step 2: Legal Documents -->
                <div class="form-step" id="form-step-2" style="display:none;">
                    <h3>Legal Documents</h3>
                    <p class="step-desc">Upload your organization's permits, registrations, and other legal documents.</p>
                    <label>Legal Documents (PDF or ZIP)</label>
                    <input type="file" name="legal_documents" accept=".pdf,.zip" required>
                    <div class="form-row">
                        <div>
                            <label>Verification Status</label>
                            <select name="verification_status">
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                        <div>
                            <label>Org Status</label>
                            <select name="org_status">
                                <option value="Pending">Pending</option>
                                <option value="Active">Active</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Contact Information (Manager) -->
                <div class="form-step" id="form-step-3" style="display:none;">
                    <h3>Manager Contact Information</h3>
                    <div class="form-row">
                        <input type="text" name="manager_first_name" placeholder="Manager First Name" required>
                        <input type="text" name="manager_last_name" placeholder="Manager Last Name" required>
                    </div>
                    <input type="email" name="manager_email" placeholder="Manager Email" required>
                    <input type="text" name="manager_phone" placeholder="Manager Phone Number" required>
                    <textarea name="manager_address" placeholder="Manager Address" required></textarea>
                    <input type="text" name="public_phone" placeholder="Public Phone Number (Optional)">
                    <input type="email" name="public_email" placeholder="Public Email (Optional)">
                </div>

                <!-- Step 4: Operations -->
                <div class="form-step" id="form-step-4" style="display:none;">
                    <h3>Operating Hours & Days</h3>
                    <div class="form-row">
                        <div>
                            <label>Opening Time</label>
                            <input type="time" name="time_open" required>
                        </div>
                        <div>
                            <label>Closing Time</label>
                            <input type="time" name="time_close" required>
                        </div>
                    </div>
                    <label>Operating Days</label>
                    <input type="text" name="operating_days" placeholder="e.g. Mon-Fri, Monday to Saturday" required>
                </div>

                <!-- Navigation Buttons -->
                <div class="modal-footer">
                    <button type="button" id="fb-prev-btn" onclick="fbPrevStep()" style="display:none;">Back</button>
                    <button type="button" id="fb-next-btn" onclick="fbNextStep()">Next</button>
                    <button type="submit" id="fb-submit-btn" style="display:none;">Register Food Bank</button>
                </div>

            </form>
        </div>
    </div>
</div>