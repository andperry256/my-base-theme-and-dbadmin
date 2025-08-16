<style>
  .pseudo-hidden {
    font-size: 0.01px;
    color: #fff;
    size: 0;
    border-style: none;
  }
</style>
<?php
//==============================================================================

print("<script type=\"text/javascript\" src=\"$base_url/common_scripts/js_for_email_link_funct.js\"></script>\n");

function display_email_addr($address)
{
    $address = str_replace('@','@<span class="pseudo-hidden">aaa</span>',$address);
    return $address;
}

function display_email_addr_image($address_id)
{
    global $email_addr_images_url;
    return "<img style=\"height:1.1em; width:auto;\" align=\"absmiddle\" src=\"$email_addr_images_url/$address_id.svg\">";
}

function display_email_copy_button($address,$include_mail_client_link=false)
{
    $id = strtok($address,'@');
    $address = str_replace('@','#',$address);
    $result = "<div style=\"display:inline-block\">";
    $result .= "<button onclick=\"copyAddresses('$id')\">Copy Address</button>";
    if ($include_mail_client_link) {
        $result .= "<div style=\"display:block; height:0.5em\">&nbsp:</div><button onclick=\"openInMailClient('$id')\">Open in Mail Client</button>";
    }
    $result .= "<input style=\"font-size:0.01em;border-style:none;color:#fff\" size=1 type=\"text\" readonly value=\"$address\" id=\"$id\">";
    $result .= "</div>";
    return $result;
}

function display_email_list_copy_button($list_id,$address_list,$include_mail_client_link=false)
{
    $address_list = str_replace('@','#',$address_list);
    $result = "<div style=\"display:inline-block\">";
    $result .= "<div style=\"display:block\"><button onclick=\"copyAddresses('$list_id')\">Copy Addresses</button>";
    if ($include_mail_client_link) {
        $result .= "<div style=\"display:block; height:0.5em\">&nbsp:</div><button onclick=\"openInMailClient('$list_id')\">Open in Mail Client</button>";
    }
    $result .= "<input style=\"font-size:0.01em;border-style:none;color:#fff\" size=1 type=\"text\" readonly value=\"$address_list\" id=\"$list_id\">";
    $result .= "</div>";
    return $result;
}

//==============================================================================
