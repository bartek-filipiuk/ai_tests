## Make API Documentation

## INTRODUCTION

### Fundamentals

The Make REST API allows using HTTP requests to access Make data and control the Make platform without opening its graphical interface. This allows you to embed Make features into your software, add features on top of the platform, and automate your tasks that you perform in Make.

To use the Make API, you need a Make account. Once logged in, you can generate an authentication token and start making calls to the API.

The API allows you to interact with multiple Make resources. This documentation covers the following API resources:

* Connections
* Data stores
* Data structures
* Hooks
* Notifications
* Organizations
* Scenarios
* Scenarios folders
* Teams
* Templates
* Users

The following API resources are fully functional, but are not covered in this documentation yet:

* Custom apps
* Devices
* Keys

### Make API structure

The root URL of the Make API consists of three parts and looks as follows:

```
{environment_url}/api/v2/{api_endpoint}
```

* **Environment URL:** The environment of Make you work in. This can be the link to your private instance of Make, for example, `https://development.make.cloud`, or the link to Make (with or without the zone, depending on a specific endpoint), for example, `https://eu1.make.com`.

> Always use HTTPS in your API requests.

* **API version:** The version of the API preceded by `/api/`

* **Endpoint (with or without parameters):** Each endpoint represents a resource that you can work with. Endpoints contain required and/or optional parameters. The resources are described in detail in Make resources.

### Getting started

This start guide will take you through making your first request to the Make API.

**Example:**

Let's imagine that you would like to list all data stores available in your team. Your team ID is 35. Returned data should be ordered in descending order.

To make your first API call, you need to perform the following actions:

**How to:**

1. Create an authentication token. The token gives you access to Make API resources depending on your Make role and assigned scopes. You must include the token in the Authorization header of all requests. Add the word `Token` and a space before the token itself:

   ```
   'Authorization: Token {Your authentication token}'
   ```

2. Choose the endpoint that corresponds to the resource you want to interact with. For this example, you need the `/data-stores` endpoint. The endpoint requires the `teamId` query parameter. Place the parameter after the question mark in the endpoint URL. To filter results, you also need the parameter for ordering data—`pg[sortDir]`:

   ```
   {environment_url}/api/v2/data-stores?teamId={teamId}&pg%5BsortDir%5D=asc
   ```

   The environment URL refers to the Make platform you interact with.

3. Prepare the full request and send it. In this case, use cURL to making the request. You want to retrieve data without modifying it—use the GET method. Let’s put elements from the previous steps together.

   The following request example contains a sample authentication token. **Don't use it in your requests.** Generate your own token.
   Always include a request body in POST, PUT, or PATCH requests.

4. Evaluate the response. The API returns `200 OK` and a list of all data stores for the specified team. If your request failed, you receive an error code. Refer to Troubleshooting and error handling to troubleshoot the issue.

**Request**

```bash
curl --location \
--request GET 'https://eu1.make.com/api/v2/data-stores?teamId=35&pg%5BsortDir%5D=asc' \
--header 'Content-Type: application/json' \
--header 'Authorization: Token 93dc8837-2911-4711-a766-59c1167a974d'
```

**Response**

```json
{
  "dataStores": [
    {
      "id": 15043,
      "name": "Old data store",
      "records": 10,
      "size": "620",
      "maxSize": "1048576",
      "teamId": 35
    },
    {
      "id": 13433,
      "name": "New data store",
      "records": 1,
      "size": "48",
      "maxSize": "1048576",
      "teamId": 35
    }
  ],
  "pg": {
    "sortBy": "name",
    "limit": 10000,
    "sortDir": "asc",
    "offset": 0
  }
}
```

## Authentication

### Make roles and API scopes

Accessibility of Make API endpoints differs depending on the Make platform you use. On Make and our hosted cloud version, regular users cannot access the administration interface. Administration API resources are meant only for internal Make administrators.

In the on-premise version, any user with a platform administration role assigned can access the administration interface. These users can also access API endpoints that are meant for administrators.

Access to the Make API resources depends also on the scopes assigned to the authentication token. Some resources require more than one scope. There are two types of scopes - read and write.

* **Read scope `:read`**: Allows you to use the GET method with endpoints, usually to get a list of resources or a resource detail. No modification is allowed.

* **Write scope `:write`**: Allows you to use the POST, PUT, PATCH, or DELETE methods with endpoints to create, modify, or remove resources.

> Even if you are not the administrator, you can assign to your token the scopes meant for administrators. However, if you try to access the admin resources as a regular user, you will receive the `403 Access denied` error in response.

**Administration scopes (only for administrators of Make White Label platforms)**

Administration

### Generating authentication token

Make API uses authentication tokens to authenticate requests. You must include your authentication token in the headers of all requests that you send to the API.

Generate and manage API tokens from your profile in the Make interface.

> If you have access to multiple Make environments, generate separate tokens for each of them.

**How to:**

1. Sign in to Make and click your avatar at the bottom-left corner of the page.
2. Click **Profile**.
3. Open the **API** tab.
4. Choose **API**
5. Click **Add token**.

**Add token**

In the **Add token** dialog, do the following:

* **Label:** Type a custom name for your token that will help you recognize what the token is used for.
* **Scopes:** Select the scopes you need for working with API resources. For more information about scopes, refer to Make roles and API scopes.
* Click **Save**.

Make generates your token. Copy it and store it in a safe place.

> **Do not share your token with anyone!**

> Once you leave the Profile section, parts of your token will be hidden for security reasons. You won't be able to see or copy your token again.

With an active token, you are ready to make API calls. For more details, refer to the **Getting started** section.

### Managing authentication token

After you generate your authentication token and open the API tab in your profile again, you can no longer change the token or the scopes assigned to the token. You can only view the initial part of the token value and view the scopes.

To manage your tokens:

**How to:**

1. Sign in to Make Make click your avatar at the bottom-left corner of the page.
2. Click **Profile**.
3. Open the **API** tab.
4. Click one of the following buttons:
  * **Show scopes**: To see scopes that are assigned to the token.
  * **Delete**: To permanently remove the token.
5. Click **Save**.

Since editing the token is not possible, you can always delete the old token and replace it with a new one. You will need to do this if you decide to add or remove scopes from your authentication token.

## Pagination, sorting and filtering

The majority of responses containing a collection of resources are paginated. Pagination limits the number of returned results per request to avoid delays in receiving a response and prevent overloading with results. Thanks to pagination, the API can run at its best performance.

You set pagination, sorting, and filtering parameters in query parameters. Separate multiple query parameters using the `&` symbol. The order of the parameters does not matter.

> Pagination and filtering parameters contain square brackets - `[` and `]`. Always encode them in URLs.

## Troubleshooting

This section describes the most common mistakes that result in API-related problems, such as receiving Access denied or Not found errors. You can also refer to the HTTP status codes of errors for more details.

* **Using HTTP instead of HTTPS in the URL:** Use HTTPS at the beginning of the URL in your request. This is required for security reasons.

* **Using an incorrect environment:** If you have access to more than one Make environment, ensure that you use the correct environment in the URL and that you use a valid authentication token generated for this specific environment.

* **Using an incorrect endpoint:** Ensure there are no empty or white spaces in the endpoint URL and that there are no backslash symbols at the end of the URL after the endpoint name.

* **Missing authentication details or using incorrect authentication details:** Ensure that you are using the correct authentication details. To make a successful request, you need to have the correct authentication token with the correct scopes assigned to it. Note that you need a separate token for each Make environment.

* **Missing access to the requested resource:** Ensure that scopes assigned to your authentication token correspond to the requested resource. Note that you cannot access administrator resources if you are a regular Make user.

* **Missing required parameters or using invalid or improperly formatted parameters:** Many endpoints require at least one mandatory parameter. Often it is the `teamId` or an ID of the specific resource. Do not forget to add the required parameters to the request. Also, note that query, path, and pagination parameters need to be properly formatted. The first query parameter should start with a question mark. Separate parameters with the ampersand symbol. Some special characters, for example, in the pagination parameters, need to be encoded when used in URLs.

* **Sending an invalid or improperly formatted request body:** The structure of the API request body must conform to the JSON schema standard. You can use JSON validators available on the internet to validate your request body before sending it.

> If your issue is not mentioned in the table above and the error code and message do not indicate how to resolve the issue, please contact us via the help form at Make Help center. Include a detailed description of the problem, the full request, and the error code and error message that you received.

## Resources

API resources are grouped into sections corresponding with Make features and components.

Each endpoint resource contains the following details:

* **Methods and endpoints:** Methods define the allowed interaction and endpoints define how to access the resource—what URI should be used to interact with a resource. Example: `GET /data-stores`

* **Required scopes:** Defines what resources you are allowed to interact with based on scopes you selected when generating your API access token. Example: `datastores:write`

* **Resource description:** Describes the expected outcome when using an endpoint, and what Make features the resource relates to.

* **Parameters:** These are options you can include with a request to modify the response. Each parameter specifies whether it is required or not. Parameters are divided into two main groups:

  * **Path parameters**: Path parameters are always required. They are used to identify or specify the resource (usually by indicating its ID) and they should be placed inside the endpoint URI. Example: `/data-stores/54`

  * **Query parameters**: Query parameters are often optional. They can be used to specify the resource but they are usually used as parameters to sort or filter resources. They are placed at the end of the endpoint URI, after a question mark. Separate multiple parameters with an ampersand symbol. If a parameter contains square brackets, encode them. Example: `/data-stores?teamId=123&pg%5Boffset%5D=10`

* **Request body**: For some endpoints (mainly connected with the POST, PUT, or PATCH HTTP methods), you can also see the **Request body** section in the endpoint details. This section contains the description of the payload properties that are needed to modify the resource.

**Example:**

```json
{
  "name": "Customers",
  "teamId": 123,
  "datastructureId": 178,
  "maxSizeMB": 1
}
```

* **Request examples:** These are request samples that show how to make a request to the endpoint. They consist of the request URL and authentication token (if needed) and other elements required to make a request in the selected language. Example of request for creating a data store:

* **Response examples:** These are response samples you would receive when calling the request in real life. The outcome strictly depends on the request sample. The response schema contains all possible elements available in the response. Each response has its status code. Example of created data store:

**Request**

```bash
curl -X POST https://eu1.make.dev/api/v2/data-stores \
--header 'Content-Type: application/json' \
--header 'Authorization: Token 93dc8837-2911-4711-a766-59c1167a974d' \
-d '{"name":"Customers","teamId":123,"datastructureId":1234,"maxSizeMB":1}'
```

**Response**

```json
{
  "dataStore": {
    "id": 20024,
    "name": "Customers",
    "teamId": "123",
    "datastructureId": 1234,
    "records": 0,
    "size": "0",
    "maxSize": "1048576"
  }
}
```
