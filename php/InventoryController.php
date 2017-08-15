<?php
namespace App\Http\Controllers\Amazon\Product;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utilities\Amazon\ProductDataUtility;
use App\Http\Controllers\Utilities\PageValidatorUtility;
use App\Product;
use App\ProductUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\User;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Utilities\Amazon\ReportGeneratorUtility;

class InventoryController extends Controller {

    public function index() 
    {
        // javascript path
        $scripts = [
            'require'   => 'js/amazon/inventory/main.js'
        ];

        // css path
        $styles = [
            'style'     => 'css/inventory.css'
        ];

        $assets = [
            'title'                 => 'Inventory',
            'scripts'               => $scripts,
            'styles'                => $styles
        ];

        // Renders the path of javascript and css files in the html template
        View::share('assets', $assets);

        // Create model instance of the user with eager loading the pricing_template and pending_get_report relationships.
        $user = User::with(['pricing_template', 'pending_get_report' => function($query) {
            // Only eager load pending_get_report that hasn't been transferred.
            $query->where('is_transferred', 0);
        // Hardcoded user_id for demonstration purposes. This would normally be the logged in user
        }])->find(1);

        $pending_get_report = $user->pending_get_report->first();

        // Pass in the $user and $pending_get_report to be rendered in the html template
        return View::make('amazon.inventory.inventory', compact('user', 'pending_get_report'));
    }

    /**
     * This fetches all the products that belongs to the user with a user_id of 1 and eager loads the product data and
     * any assigned pricing template of those products.
     *
     * @return mixed
     */
    public function getInventory() 
    {
        $products = ProductUser::with('product', 'pricing_template')
            // Hardcoded user_id for demonstration purposes. This would normally be the logged in user
            ->where('user_id', 1)
            ->get();

        // Formats the fetched data to be compatible with Datatables.
        return Datatables::of($products)->make(true);
    }

    /**
     * Fetch a single product based on product_id
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProduct(Request $request)
    {
        // Validate product_id to be required in the $request data
        $validator = Validator::make($request->all(), [
            'product_id'    => 'required'
        ]);

        // Error out if required validation fails
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        try {
            // Creates a model instance of a user and eager load product relationship based on product_id
            $user = User::with(['products' => function($query) use ($request){
                $query->where('product_id', $request->input('product_id'));
            // Hardcoded user_id for demonstration purposes. This would normally be the logged in user
            }])->find(1);


            /**
             * Products relationship is many to many and returns a collection. Calling first() is what we needs since it
             * converts the relationship into a single model
             */
            $product = $user->products->first();

            // Throw an exception if the product is not found
            if (!$product) throw new ModelNotFoundException;

            $product_data = new ProductDataUtility($product->amaz_product_id);

            // Product data that gets rendered out into the page.
            $product->buy_box = $product_data->getBuyBox();
            $product->image_link = $product_data->getImageLink('MediumImage');
            $product->detail_page_url = $product_data->getDetailPageURL();

            return response()->json($product);

        } catch (ModelNotFoundException $e) {
            return response()->json('Not Found', 400);
        }
    }

    /**
     * Fetch the pricing change history of a product
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPricingHistory(Request $request)
    {
        // Validate product_id to be required in the $request data
        $validator = Validator::make($request->all(), [
            'product_id'    => 'required'
        ]);

        // Error out if required validation fails
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        try {
            // Creates an model instance of a product based on product_id and logged in user
            $product_user = ProductUser::where('product_id', $request->input('product_id'))
                // Hardcoded user_id for demonstration purposes. This would normally be the logged in user
                ->where('user_id', 1)
                ->first();

            // Throw an exception if the specific product isn't found
            if (!$product_user) throw new ModelNotFoundException;

            // Filter the revision history of the product to just price changes.
            $pricing_history = $product_user->revisionHistory->where('key', 'price');

            // Formats the pricing history to be compatible with Datatables.
            return Datatables::of($pricing_history)->make(true);

        } catch (ModelNotFoundException $e) {
            return response()->json('Not Found', 400);
        }
    }
    /**
     * Updates the min, max, pricing template of a product
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProduct(Request $request)
    {
        // Validate product_id, min_price, max_price, pricing_template_id to be required
        $validator = Validator::make($request->all(), [
            'product_id'            => 'required',
            'min_price'             => 'required|numeric',
            'max_price'             => 'required|numeric',
            'pricing_template_id'   => 'required|numeric',
        ]);

        // Error out if required validation fails
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        // Use the query builder to update the product's data
        DB::table('product_user')
            ->where('product_id', $request->input('product_id'))
            // Hardcoded user_id for demonstration purposes. This would normally be the logged in user
            ->where('user_id', 1)
            ->update([
                'min_price'             => $request->input('min_price'),
                'max_price'             => $request->input('max_price'),
                'pricing_template_id'   => $request->input('pricing_template_id'),
            ]);
    }

    /**
     * Creates an entry in the pending_get_report table which acts as a request for a need of an inventory update.
     */
    public function updateInventory()
    {
        // Hardcoded user_id for demonstration purposes. This would normally be the logged in user
        $user = User::find(1);

        (new ReportGeneratorUtility())->generateReport($user);
    }
}