jQuery(document).ready(function($) {
    // Search products dynamically
    $('#product_search').on('keyup', function() {
        let searchQuery = $(this).val();
        if (searchQuery.length < 3) return; // Wait until at least 3 characters

        $.ajax({
            url: adminAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'search_products',
                query: searchQuery
            },
            success: function(response) {
                let resultsDiv = $('#search_results');
                resultsDiv.empty();
                
                if (response.success) {
                    response.data.forEach(product => {
                        let btn = $('<button>')
                            .text(product.name)
                            .addClass('select_product button')
                            .attr('data-id', product.id)
                            .attr('data-name', product.name)
                            .attr('data-price', product.price);

                        resultsDiv.append(btn);
                    });
                } else {
                    resultsDiv.text('No products found.');
                }
            }
        });
    });

    // Handle product selection and fetch variations
    $(document).on('click', '.select_product', function() {
        let productId = $(this).data('id');
        let productName = $(this).data('name');
        let productPrice = $(this).data('price');

        $('#product_variations').hide().empty();

        $.ajax({
            url: adminAjax.ajax_url,
            type: 'POST',
            data: { action: 'get_product_variations', product_id: productId },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let variationDropdown = $('#product_variations').show();
                    response.data.forEach(variation => {
                        variationDropdown.append(
                            $('<option>')
                                .val(variation.id)
                                .text(variation.name + ' - ' + variation.price)
                                .attr('data-price', variation.price)
                        );
                    });
                }
            }
        });

        $('#add_product').data('id', productId).data('name', productName).data('price', productPrice);
    });

    // Add product to table
    $('#add_product').on('click', function() {
        let selectedProduct = {
            id: $(this).data('id'),
            name: $(this).data('name'),
            price: $(this).data('price'),
            quantity: 1
        };

        let variationSelected = $('#product_variations').val();
        if (variationSelected) {
            selectedProduct.id = variationSelected;
            selectedProduct.price = $('#product_variations option:selected').data('price');
            selectedProduct.name += ' (' + $('#product_variations option:selected').text() + ')';
        }

        let newRow = `<tr>
            <td>${selectedProduct.name}</td>
            <td>${selectedProduct.price}</td>
            <td><input type="number" value="1" min="1" class="product-quantity" data-id="${selectedProduct.id}"></td>
            <td><button type="button" class="remove-product button">Remove</button></td>
        </tr>`;

        $('table.widefat tbody').append(newRow);
    });

    // Remove product from table
    $(document).on('click', '.remove-product', function() {
        $(this).closest('tr').remove();
    });
});
