 <div class="modal fade" id="recurringItemModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
     <div class="modal-content">

       <div class="modal-header border-bottom py-3 px-4">
         <h5 class="modal-title fw-bold" id="modalTitle" style="font-size:.95rem;">Add Recurring Item</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       </div>

       <div class="modal-body px-4 py-4">
         <div class="row g-3 mb-3">
           <div class="col-6">
             <label class="ss-label">Type <span class="ss-required">*</span></label>
             <select class="ss-input" id="itemType">
               <option value="Earning">Earning</option>
               <option value="Deduction">Deduction</option>
             </select>
           </div>
           <div class="col-6">
             <label class="ss-label">Category <span class="ss-required">*</span></label>
             <select class="ss-input" id="itemCategory">
               <option value="sss_salary_loan">SSS Salary Loan</option>
               <option value="sss_calamity_loan">SSS Calamity Loan</option>
               <option value="hdmf_loan">HDMF Loan</option>
               <option value="hdmf_calamity_loan">HDMF Calamity Loan</option>
               <option value="sss_mutual_fund">SSS Mutual Fund</option>

               <option value="fb_real_estate_loan">FB Real Estate Loan</option>
               <option value="fb_vehicle_loan">FB Vehicle Loan</option>
               <option value="fb_appliance_loan">FB Appliance Loan</option>
               <option value="fb_medical_loan">FB Medical Loan</option>
               <option value="fb_medical_loan_2">FB Medical Loan 2</option>
               <option value="fb_personal">FB Personal</option>
               <option value="fb_motorcycle">FB Motorcycle</option>
               <option value="fb_appliance">FB Appliance</option>
               <option value="fb_vehicle">FB Vehicle</option>
               <option value="fb_medical_1">FB Medical 1</option>
               <option value="fb_medical_2">FB Medical 2</option>
               <option value="fb_educational">FB Educational</option>

               <option value="housing_loan">Housing Loan</option>
               <option value="accounts_receivable">Accounts Receivable</option>

               <option value="sss_loan">SSS Loan</option>
               <option value="pagibig_loan">PAG-IBIG Loan</option>
             </select>
           </div>
         </div>

         <div class="row g-3 mb-3">
           <div class="col-6">
             <label class="ss-label">Amount <span class="ss-required">*</span></label>
             <div class="ss-input-currency">
               <span class="ss-currency">₱</span>
               <input type="number" class="ss-input" id="itemAmount" placeholder="0.00" min="0" step="0.01">
             </div>
           </div>
           <div class="col-6">
             <label class="ss-label">Apply On <span class="ss-required">*</span></label>
             <select class="ss-input" id="itemApplyOn">
               <option value="Both Cutoffs">Both Cutoffs</option>
               <option value="1st Cutoff Only">1st Cutoff Only</option>
               <option value="2nd Cutoff Only">2nd Cutoff Only</option>
             </select>
           </div>
         </div>

         <div class="row g-3">

           <div class="col-6">
             <label class="ss-label">Status</label>
             <div class="ss-toggle-row">
               <label class="ss-toggle">
                 <input type="checkbox" id="itemStatus" checked>
                 <span class="ss-slider"></span>
               </label>
               <span class="ss-toggle-label" id="itemStatusLabel">Active</span>
             </div>
           </div>
         </div>
       </div>

       <div class="modal-footer border-top py-3 px-4">
         <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
         <button type="button" class="btn btn-primary btn-sm ss-btn-save" id="modalSave">
           <i class="ri-save-2-line me-1"></i> Save Item
         </button>
       </div>

     </div>
   </div>
 </div>
