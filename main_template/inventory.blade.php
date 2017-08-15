@include('layouts.navTabs')
<div class="max_width min_width row center-block inventory_container">
    <div class="col-xs-12 inventory_update_container">
        <div class="row">
            <div class="col-xs-6 update_inventory_button_container" data-pending_update="{{ $pending_get_report->is_transferred or 1 }}">

            </div>
            <div class="col-xs-6 last_inventory_update_date_container">
                @if(strtotime($user->inventory_updated_at) > 0 )
                    <div class="col-xs-12">
                        <strong class="pull-right">Last Inventory Update </strong>
                    </div>
                    <div class="col-xs-12">
                        <span class="pull-right">{{ date('m-d-Y h:i A', strtotime($user->inventory_updated_at)) }}</span>
                    </div>
                    <div class="col-xs-12 pull-right">
                        <span class="utc_font_size pull-right"><strong>(UTC-06:00)</strong></span>
                    </div>
                @else
                    <div class="col-xs-12 pull-right">
                        {{'N/A'}}
                    </div>
                @endif
            </div>
            <div class="col-xs-12 update_inventory_text_container md_pd_top">

            </div>
        </div>
    </div>
    <div id="container" class="col-xs-12 md_pd_top lg_pd_right lg_pd_left" data-container_type="{{ isset($assets['title']) ? strtolower(preg_replace('/\s+/', '_', $assets['title']))  : ' ' }}">
        <div class="row">
            <div class="col-xs-12">
                <table id="datatable" class="table table-striped table-bordered dataTable_font products_table" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>ASIN</th>
                        <th>SKU</th>
                        <th>Item Name</th>
                        <th>Pricing Template</th>
                        <th>Latest Price Update</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('amazon.inventory.modals.editProduct')
@include('amazon.inventory.modals.pricingHistory')

<script id="last_price_update_template" type="template">
    <div class="row">
        <div class="col-xs-12">$<%= price %></div>
        <div class="col-xs-12"><%= formatted_date %></div>
        <div class="col-xs-12">
            <span class="utc_font_size"><strong><%= timezone %></strong></span>
        </div>
    </div>
</script>

<script id="edit_product_button_template" type="template">
    <div class="row inventory_desktop_action_buttons">
        <div class="col-lg-12 sm_pd_right md_pd_left">
            <button type="button" class="btn btn-sm btn-info edit_product" data-product_id="<%= full['product_id'] %>" data-toggle="modal" data-target="#edit_product_modal">
                <span class="glyphicon glyphicon-edit"></span> Edit Pricing
            </button>

            <button type="button" class="btn btn-sm btn-primary pricing_history" data-product_id="<%= full['product_id'] %>" data-amaz_item_name="<%= full['product'].amaz_item_name %>" data-toggle="modal" data-target="#pricing_history_modal">
                <span class="glyphicon glyphicon glyphicon-list-alt"></span> Pricing History
            </button>
        </div>
    </div>
    <div class="row inventory_mobile_action_buttons">
        <div class="col-xs-12">
            <button type="button" class="btn btn-sm btn-info edit_product" data-product_id="<%= full['product_id'] %>" data-toggle="modal" data-target="#edit_product_modal">
                <span class="glyphicon glyphicon-edit"></span> Edit Pricing
            </button>
        </div>
        <div class="col-xs-12 sm_pd_top">
            <button type="button" class="btn btn-sm btn-primary pricing_history" data-product_id="<%= full['product_id'] %>" data-amaz_item_name="<%= full['product.amaz_item_name'] %>" data-toggle="modal" data-target="#pricing_history_modal">
                <span class="glyphicon glyphicon glyphicon-list-alt"></span> Pricing History
            </button>
        </div>
    </div>
</script>

<script id="update_inventory_button_template" type="template">
    <button type="button" class="btn btn-sm btn-success update_inventory" data-user_id="{{ $user->id }}" ><span class="glyphicon glyphicon glyphicon-refresh"></span> Update Inventory</button>
</script>

<script id="update_in_progress_button_template" type="template">
    <button type="button" class="btn btn-sm btn-default update_inventory" data-user_id="{{ $user->id }}" disabled><img src="https://d2odfdz8dgrqu.cloudfront.net/images/loader.gif"></span> Update in Progress</button>
    <span class="update_inventory_text_desktop md_mg_left hidden-xs"> Inventory Update in Progress. Please check back in 5 to 10 minutes.</span>
</script>

<script id="update_in_progress_text_template" type="template">
    Inventory Update in Progress. Please check back in 5 to 10 minutes.
</script>
@include('layouts.footer')

