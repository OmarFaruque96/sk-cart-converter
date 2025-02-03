<div id="edit-cart-modal" style="display:none; position:fixed; top:20%; left:30%; width:40%; background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
    <h2>Edit Abandoned Cart</h2>
    <form id="edit-cart-form">
        <input type="hidden" name="cart_id">
        <input type="hidden" name="action" value="act_edit_abandoned_cart">
        
        <label>User Name:</label>
        <input type="text" name="user_name" required>
        
        <label>Email:</label>
        <input type="email" name="email" required>
        
        <label>Phone:</label>
        <input type="text" name="phone">
        
        <label>Additional Text:</label>
        <textarea name="additional_text"></textarea>
        
        <button type="submit">Update</button>
        <button type="button" id="close-modal">Cancel</button>
    </form>
</div>
