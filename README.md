Donation Barometer Implementation Guide
=======================================


The donation barometer consists of two main components:
1. one server-side component which queries the RaiseNow API
2. the frontend component which displays the donation amount statistic


## Prerequisites
In order to develop your own donation barometer, you'll need the following:

* A RaiseNow Account for [manage.raisenow.com](https://manage.raisenow.com)
* The RaiseNow Merchant Identifier
* A RaiseNow API user to query the RaiseNow API. You can create one following this Knowledge Base article: 
  [How to create a new user in the RaiseNow Manager](https://support.raisenow.com/hc/en-us/articles/360001440097-How-to-create-a-new-user-in-the-RaiseNow-Manager)
  Make sure that you set API permissions and the flag for an API user as otherwise your user will expire.


### Server-Side Component

In order to aggregate donations made through RaiseNow for your organisation,
you can query the RaiseNow search API from your server-side component. Once you've obtained
the donation statistics, your server-side component can expose an API to your 
frontend. 

_Why should you use a server-side component to query the search API?_ 
The search API is protected by credentials, querying the API from the frontend
will expose these credentials to the public, allowing anyone to use them
and query as well as update your data at RaiseNow. It is your responsibility
to keep these credentials secure.

#### Authorization
To access the RaiseNow API, you need to authenticate the request
using HTTP Basic Authentication with your API user credentials.


#### Search Query
To obtain statistics of your received donations, you can use the following API request: 

```
GET https://api.raisenow.com/epayment/api/<MERCHANT_IDENTIFIER>/transactions/search?<QUERY>'
```

whereas 
* `MERCHANT_IDENTIFIER` is your RaiseNow merchant identifier as obtained in the section `Prerequisites`.
* `QUERY` is the search query as a URL-encoded string. It follows the following format:
  * A _sort_ part, which describes how donation should be sorted. 
    Using the created date and sort it in descending manner will give you the most recent donation.
    * `sort[0][field_name]=created`
    * `sort[0][order]=desc`
  * A _filter_ part which will filter the returned transactions. 
    The following will return all successful production donations made in CHF.
    * `filters[0][field_name]=last_status`
    * `filters[0][type]=term`
    * `filters[0][value]=final_success`
    * `filters[1][field_name]=test_mode`
    * `filters[1][type]=term`
    * `filters[1][value]=production`
    * `filters[2][field_name]=currency_identifier`
    * `filters[2][type]=term`
    * `filters[2][value]=chf`
  * A _facet_ part which will define how the donations are aggregated.
    Among the total sum of donations having matched your filter query,
    this facet will give you also the max, min, avg, and std. deviation.
    Note that the name of the facet is also contained in the response. 
    This is especially useful if you are defining multiple facets.
    * `facets[0][field_name]=amount`
    * `facets[0][type]=stats`
    * `facets[0][name]=total_amount`
  * A section which defines what kind of fields are returned by the search query.
    * `displayed_fields=epp_transaction_id,created,currency_identifier,amount,payment_method_identifier`
  * A section which defines how many records per page should be returned.
    * `records_per_page=5`
  * You can find the complete list of options also in the 
    [RaiseNow Developer Docs](https://developer.raisenow.com/docs/api/#payment-management-search) and further examples
    of queries in our Knowledge Base [Transaction Search API](https://support.raisenow.com/hc/en-us/articles/115005300269-Transaction-Search-API).
  
This will give you a response looking as follows:
```
{
  "result": {
    "transactions": [
      {
        "epp_transaction_id": "<TRANACTION_IDENTIFIER>",
        "created": "1642428270", 
        "currency_identifier": "<CURRENCY>",
        "amount": <AMOUNT_IN_CENTS>,
        "payment_method_identifier": "<PAYMENT_METHOD_IDENTIFIER>"
      },
      {
        "epp_transaction_id": "<TRANACTION_IDENTIFIER>",
        "created": "1640073719",
        "currency_identifier": "<CURRENCY>",
        "amount": <AMOUNT_IN_CENTS>,
        "payment_method_identifier": "<PAYMENT_METHOD_IDENTIFIER>"
      },
      {
        "epp_transaction_id": "<TRANACTION_IDENTIFIER>",
        "created": "1639470666",
        "currency_identifier": "<CURRENCY>",
        "amount": <AMOUNT_IN_CENTS>,
        "payment_method_identifier": "<PAYMENT_METHOD_IDENTIFIER>"
      },
      {
        "epp_transaction_id": "<TRANACTION_IDENTIFIER>",
        "created": "1639415198",
        "currency_identifier": "<CURRENCY>",
        "amount": <AMOUNT_IN_CENTS>,
        "payment_method_identifier": "<PAYMENT_METHOD_IDENTIFIER>"
      },
      {
        "epp_transaction_id": "<TRANACTION_IDENTIFIER>",
        "created": "1639412837",
        "currency_identifier": "<CURRENCY>",
        "amount": <AMOUNT_IN_CENTS>,
        "payment_method_identifier": "<PAYMENT_METHOD_IDENTIFIER>"
      }
    ],
    "sort": [
      {
        "fieldname": "created.raw",
        "order": "desc"
      }
    ],
    "additional_info": {
      "lang": "de",
      "search_time": "138.7160",
      "total_hits": 900,
      "page": 1,
      "total_pages": 180,
      "records_per_page": "5"
    },
    "filters": [
      {
        "field_name": "last_status",
        "type": "term",
        "value": "final_success",
        "filter_group": 0
      },
      {
        "field_name": "test_mode",
        "type": "term",
        "value": "production",
        "filter_group": 1
      },
      {
        "field_name": "currency_identifier",
        "type": "term",
        "value": "chf",
        "filter_group": 2
      }
    ],
    "facets": [
      {
        "name": "total_amount",
        "type": "stats",
        "stats": {
          "min": 1,
          "max": 5000,
          "mean": 120.93333333333,
          "count": 900,
          "total": 108840,
          "standard_deviation": 324.61111092095,
          "sum_of_squares": 107997520,
          "variance": 105372.37333333
        }
      }
    ]
  }
}
```
  
whereas

* `transactions` contains up to `records_per_page` transactions having matched your filter query. The variables are defined as follows: 
    * `TRANACTION_IDENTIFIER` is the identifier of the donation at RaiseNow
    * `CURRENCY` is the currency in which the donation has been made, e.g. `chf`
    * `AMOUNT_IN_CENTS` is the amount of the donation in cents
    * `PAYMENT_METHOD_IDENTIFIER` the identifier of the payment method
* `sort` describes by which field has been sorted
* `additional_info` gives you an impression of how many records have matched your query
* `filters` describe which filter have been applied in the query
* `facets` gives you detailed information 

Typically, your server side component will just return the `total` of the `facets` section
in order to show the total collected amount.

### Example
You can find an example of a PHP server side component in `server/server.php`.

To run it, follow these steps:

1. Install [composer](https://getcomposer.org) dependencies using `composer install`
2. Run `php server.php`


### Frontend Component
Once you have your server-side component ready, you can use the code snippet in `client/index.html`
to display your barometer.
