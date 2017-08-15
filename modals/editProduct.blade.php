<div id="edit_product_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-info">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Edit Pricing</h4>
            </div>
            <div class="edit_product_form_container">

            </div>
        </div>
    </div>
</div>

<script id="edit_product_form_template" type="template">
    <div class="modal-body">
        <div class="row">
            <div class="col-xs-12 center_content sm_pd_btm">
                <a href="<%= data.detail_page_url %>"><h5><%= data.amaz_item_name %></h5></a>
            </div>
            <div class="col-xs-12">
                <canvas id="pricing_chart"></canvas>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 lg_pd_top center_content">
                <a href="<%= data.detail_page_url %>"><img src="<%= data.image_link %>"></a>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 lg_pd_top">
                <div class="form-group min_price_container">
                    <label for="min_price" class="control-label">Minimum Allowable Price: </label>
                    <div class="controls">
                        <input type="text" class="form-control min_price" value="<%= data.pivot.min_price %>">
                    </div>
                    <div class="min_price_error_container">

                    </div>
                </div>
                <div class="form-group max_price_container">
                    <label for="max_price" class="control-label">Maximum Allowable Price: </label>
                    <div class="controls">
                        <input type="text" class="form-control max_price" value="<%= data.pivot.max_price %>">
                    </div>
                    <div class="max_price_error_container">

                    </div>
                </div>
            </div>
            <div class="col-xs-12 lg_pd_top">
                <p>You can unassign a pricing template of this product by selecting <strong>Not Set</strong> or assign a new pricing template by selecting from the dropdown list.</p>
                <label for="pricing_template_id" class="control-label">Pricing Template: </label>
                <select class="form-control pricing_template_id">
                    <option value="0" <%= (data.pivot.pricing_template_id == 0) ? 'selected' : '' %>><i>Not set</i></option>
                    @foreach ($user->pricing_template as $pricing_template)
                        <option value="{{ $pricing_template->id }}" <%= (data.pivot.pricing_template_id == {{ $pricing_template->id }}) ? 'selected' : '' %>>{{ $pricing_template->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn btn-info update_product" data-product_id="<%= data.id %>"><span class="glyphicon glyphicon-check"></span> Save</button>
    </div>
</script>