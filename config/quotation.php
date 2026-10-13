<?php

return [
    'business_name' => env('BUSINESS_NAME', env('APP_NAME', 'ShopICT')),
    'dealer_line' => env('BUSINESS_DEALER_LINE', 'Dealers in: All IT Products: New Laptops, Monitors, Printers, Servers, POS, Desktops, All-in-ones'),
    'logo' => env('BUSINESS_LOGO', 'logo.png'),
    'phone' => env('BUSINESS_PHONE', '0713 869 018'),
    'po_box' => env('BUSINESS_PO_BOX', '75765-00200 Nairobi'),
    'email' => env('BUSINESS_EMAIL', 'shopictgadgets@gmail.com'),
    'address' => env('BUSINESS_ADDRESS', 'Biashara Street, Laxmi Plaza, 3rd Floor, Shop 5'),
    'website' => env('BUSINESS_WEBSITE', 'https://shopictgadgets.co.ke/'),
    'served_by' => env('QUOTATION_SERVED_BY', 'James Ndiba'),
    'payment_method' => env('QUOTATION_PAYMENT_METHOD', 'Family Bank'),
    'paybill' => env('QUOTATION_PAYBILL', '222111'),
    'payment_account' => env('QUOTATION_PAYMENT_ACCOUNT', '713026'),
    'payment_account_name' => env('QUOTATION_PAYMENT_ACCOUNT_NAME', 'IT GADGETS SHOP LIMITED'),
    'registration' => env('BUSINESS_REGISTRATION'),
    'terms' => env('QUOTATION_TERMS'),
];
