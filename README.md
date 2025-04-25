# Product-Tag-Manager
Manage product tags by integrating Stripe products and Mailchimp.
![Screenshot_1_product_tags](https://github.com/user-attachments/assets/33315b61-f251-4ee3-82fa-a4926800a14f)

![Screenshot_2_product_tags](https://github.com/user-attachments/assets/f180c698-9547-43a3-945d-3c307376482e)


**1. Download the Plugin ZIP File**

    Navigate to the plugin's GitHub repository.

    Open cmd and write command "composer install"
    

**2. Install the Plugin in WordPress**

    Log in to your WordPress admin dashboard.

    Go to Plugins > Add New.

    Click on "Upload Plugin".

    Choose the ZIP file you downloaded and click "Install Now".

    After installation, click "Activate Plugin".​
   

**3. Configure API Keys**

    In the WordPress admin menu, navigate to Product Tags > API Settings.

    Enter your Stripe API Key, Mailchimp API Key, and Mailchimp List ID.

    Click "Save Changes" to store your settings.​

**4. Manage Product Tags**

    Go to Product Tags in the admin menu.

    Here, you can assign Mailchimp tags to your Stripe products using the interface provided.

**Use following URL in your Stripe's web hook response URL:
https://www.yourdomain.com/wp-json/stripe-products-mailchimp/v1/webhook**
