MailRelay integration plugin

This plugin implements the MailRelay subscription form functionality for the [OctoberCMS](http://octobercms.com).

It's based in the [MailChimp integration plugin](https://octobercms.com/plugin/rainlab-mailchimp) by [RainLab](https://octobercms.com/author/RainLab).

## Configuring

In order to use the plugin you need to get the API key from your [MailRelay account](https://your-account-name.ipzmarketing.com/admin/api_keys/).

1. In the OctoberCMS back-end go to the System / Settings page and click the MailRelay link.
2. Write your MailRelay account name in the **MailRelay Account Name** field.
3. Paste the API key in the **MailRelay API key** field.

## Creating the Signup form

You can put the MailRelay signup form on any front-end page. Add the MailRelay Signup Form component to a page or layout. Close the Inspector and save the page. 

The simplest way to add the signup form is to use the component's default partial and the `{% component %}` tag. Add it to a page or layout where you want to display the form:

    {% component 'mailSignup' %}

If the default partial is not suitable for your website, replace the component tag with custom code, for example:

    <form
        id="subscribe-form"
        data-request="mailSignup::onSignup"
        data-request-update="'mailSignup::result': '#subscribe-form'"
    >
        <input type="text" name="email" placeholder="Newsletter subscription">
        <input type="submit" class="btn btn-default" value="Subscribe"/>
    </form>

The example uses the standard partial mailSignup::result for displaying the subscription confirmation message. If you don't like the standard message you can create your own partial in your theme and specify its name in the `data-request-update` attribute. The default partial is located in `plugins/rainlab/MailRelay/components/signup/result.htm`.

More fields can be included in the subscription request:
    
    <input type="text" name="merge[FNAME]" placeholder="First Name" />
    <input type="text" name="merge[LNAME]" placeholder="Last Name" />

That's it!