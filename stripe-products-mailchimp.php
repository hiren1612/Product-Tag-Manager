<?php
/*
Plugin Name: Product Tag Manager
Description: Manage product tags by integrating Stripe and Mailchimp.
Version: 1.0
Author: Hiren Patel
*/


require_once __DIR__ . '/vendor/autoload.php';

add_action('admin_init', 'ptm_register_settings');

function ptm_register_settings() {
    register_setting('ptm_settings_group', 'ptm_stripe_api_key');
    register_setting('ptm_settings_group', 'ptm_mailchimp_api_key');
    register_setting('ptm_settings_group', 'ptm_mailchimp_list_id');
}

// Fetch Mailchimp tags
function fetch_mailchimp_tags() {

    $apiKey = get_option('ptm_mailchimp_api_key');
    $listId = get_option('ptm_mailchimp_list_id');

    if (!$apiKey || !$listId) {
        return [];
    }
    
    $dc = substr($apiKey, strpos($apiKey, '-') + 1); // Extract Data Center

    $all_tags = [];
    $offset = 0;
    $count = 500; // Fetch 10 tags per request

    do {
        // Define API URL with pagination parameters
        $url = "https://$dc.api.mailchimp.com/3.0/lists/$listId/tag-search?count=$count&offset=$offset";

        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "user:$apiKey");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        // Execute cURL request
        $response = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response
        $tags_data = json_decode($response, true);

        // Extract tag names and add to the all_tags array
        if (!empty($tags_data['tags'])) {
            foreach ($tags_data['tags'] as $tag) {
                $all_tags[] = $tag['name'];
            }
        }

        // If the number of fetched tags is less than the count, stop fetching
        $fetched_count = !empty($tags_data['tags']) ? count($tags_data['tags']) : 0;
        $offset += $count;

    } while ($fetched_count == $count); // Continue if fetched records match count

    return $all_tags;
}


// Hook to add the admin menu page
add_action('admin_menu', 'custom_product_tag_menu');

function custom_product_tag_menu() {
    add_menu_page(
        'Product Tags',
        'Product Tags',
        'manage_options',
        'product-tags',
        'product_tags_page',
        'dashicons-tag',
        20
    );

    add_submenu_page(
        'product-tags',
        'API Settings',
        'API Settings',
        'manage_options',
        'ptm-api-settings',
        'ptm_api_settings_page'
    );
}

function ptm_api_settings_page() {
    ?>
    <div class="wrap">
        <h1>API Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ptm_settings_group');
            do_settings_sections('ptm_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Stripe API Key</th>
                    <td><input type="text" name="ptm_stripe_api_key" value="<?php echo esc_attr(get_option('ptm_stripe_api_key')); ?>" size="50" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mailchimp API Key</th>
                    <td><input type="text" name="ptm_mailchimp_api_key" value="<?php echo esc_attr(get_option('ptm_mailchimp_api_key')); ?>" size="50" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mailchimp List ID</th>
                    <td><input type="text" name="ptm_mailchimp_list_id" value="<?php echo esc_attr(get_option('ptm_mailchimp_list_id')); ?>" size="50" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}



// Fetch Stripe products
function fetch_stripe_products() {

    $stripe_api_key = get_option('ptm_stripe_api_key');
    if (!$stripe_api_key) {
        return [];
    }

    \Stripe\Stripe::setApiKey($stripe_api_key);

    try {
        $products = \Stripe\Product::all(["limit" => 100]);
        return $products->data;
    } catch (Exception $e) {
        return [];
    }
}

// Admin Page Content
function product_tags_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submission
    if (isset($_POST['product_title']) && isset($_POST['product_tag'])) {
        $product_title = sanitize_text_field($_POST['product_title']);
        $product_tag = sanitize_text_field($_POST['product_tag']);

        // Retrieve existing data from options
        $saved_tags = get_option('custom_product_tags', []);

        // Ensure it's an array
        if (!is_array($saved_tags)) {
            $saved_tags = [];
        }

        // Add new entry
        $saved_tags[] = ['title' => $product_title, 'tag' => $product_tag];

        // Update the option in the database
        update_option('custom_product_tags', $saved_tags);

        echo '<div class="updated"><p>Product tag saved successfully!</p></div>';
    }

    // Fetch saved product tags
    $saved_tags = get_option('custom_product_tags', []);
    $products = fetch_stripe_products();
    $mailchimp_tags = fetch_mailchimp_tags();
    ?>
    <div class="wrap">
        <h1>Manage Product Tags</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="product_title">Product</label></th>
                    <td>
                        <select name="product_title" required class="regular-text">
                            <option value="">Select a Product</option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?php echo esc_attr($product->name); ?>">
                                    <?php echo esc_html($product->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="product_tag">Tag</label></th>
                    <td>
                        <select name="product_tag" required class="regular-text">
                            <option value="">Select a Tag</option>
                            <?php foreach ($mailchimp_tags as $tag) : ?>
                                <option value="<?php echo esc_attr($tag); ?>">
                                    <?php echo esc_html($tag); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p><input type="submit" value="Save Product Tag" class="button button-primary"></p>
        </form>

        <h2>Saved Product Tags</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Product Title</th>
                    <th>Tag</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($saved_tags)) : ?>
                    <?php foreach ($saved_tags as $index => $entry) : ?>
                        <tr>
                            <td><?php echo esc_html($entry['title']); ?></td>
                            <td><?php echo esc_html($entry['tag']); ?></td>
                            <td>
                                <a href="?page=product-tags&delete=<?php echo $index; ?>" class="button button-small button-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="3">No product tags saved yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php

    // Handle deletion of a product tag
    if (isset($_GET['delete'])) {
        $index_to_remove = (int) $_GET['delete'];
        if (isset($saved_tags[$index_to_remove])) {
            unset($saved_tags[$index_to_remove]);
            update_option('custom_product_tags', array_values($saved_tags));
            echo '<script>window.location.href="?page=product-tags";</script>';
            exit;
        }
    }
}
