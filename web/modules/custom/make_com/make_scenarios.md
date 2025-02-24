## Scenarios

Scenarios allow you to create and run automation tasks. A scenario consists of a series of modules that indicate how data should be transferred and transformed between apps or services. The following endpoints allow you to create, manage and execute scenarios and also inspect and manage scenario inputs.

---
## List scenarios

**Method & Endpoint:** `GET /scenarios`

**Required Scopes:** `scenarios:read`

**Description:** Retrieves a collection of all scenarios for a team or an organization with a given ID. Returned scenarios are sorted by proprietary setting in descending order.

### Query parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `teamId` | integer | **Required** | The unique ID of the team whose scenarios will be retrieved. If this parameter is set, the `organizationId` parameter must be skipped. For each request either `teamId` or `organizationId` must be defined. | `1` |
| `organizationId` | integer |  | The unique ID of the organization whose scenarios will be retrieved. If this parameter is set, the `teamId` parameter must be skipped. For each request either `teamId` or `organizationId` must be defined. | `11` |
| `id[]` | array [integer] |  | The array of IDs of scenarios to retrieve. | `[1, 2, 3]` |
| `folderId` | integer |  | The unique ID of the folder containing scenarios you want to retrieve. | `1` |
| `islinked` | boolean |  | If set to true, this parameter filters only active scenarios for which the schedule is enabled. | `true` |
| `concept` | boolean |  | If set to true, the response contains only scenario concepts. | `true` |
| `cols[]` | array[Enum<values>] |  | Specifies columns that are returned in the response. Use the `cols[]` parameter for every column that you want to return in the response. For example `GET /endpoint?cols[]=key1&cols[]=key2` to get both `key1` and `key2` columns in the response. Check the "Filtering" section for a full example. |  |
| `pg[offset]` | integer |  | The value of entities you want to skip before getting entities you need. |  |
| `pg[limit]` | integer |  | The value of maximum entities to return. |  |
| `pg[sortBy]` | Enum<values> |  | The value that will be used to sort returned entities by. |  |
| `pg[sortDir]` | Enum<values> |  | The sorting order. It accepts the ascending and descending direction specifiers. |  |

### Request Example (PHP http1)

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios');
$request->setMethod(HTTP_METH_GET);

$request->setQueryData([
  'teamId' => '1',
  'organizationId' => '11',
  'id[]' => '1,2,3',
  'folderId' => '1',
  'islinked' => 'true',
  'concept' => 'true'
]);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "scenarios": [
    {
      "id": 925,
      "name": "New scenario",
      "teamId": 215,
      "hookId": null,
      "deviceId": null,
      "deviceScope": null,
      "concept": false,
      "description": "",
      "folderId": null,
      "isinvalid": false,
      "islinked": false,
      "islocked": false,
      "isPaused": false,
      "usedPackages": [
        "json"
      ],
      "lastEdit": "2021-09-22T06:40:56.692Z",
      "scheduling": {
        "type": "indefinitely",
        "interval": 900
      },
      "iswaiting": false,
      "dlqCount": 0,
      "createdByUser": {
        "id": 985,
        "name": "John Doe",
        "email": "j.doe@example.com"
      },
      "updatedByUser": {
        "id": 986,
        "name": "John Foo",
        "email": "j.foo@example.com"
      },
      "nextExec": "2021-09-22T06:41:56.692Z",
      "created": "2021-10-22T06:41:56.692Z"
    }
  ],
  "pg": {
    "sortBy": "id",
    "sortDir": "desc",
    "offset": 0,
    "limit": 10
  }
}
```

---
## Create scenario

**Method & Endpoint:** `POST /scenarios`

**Required Scopes:** `scenarios:write`

**Description:** Creates a new scenario with data passed in the request body. In the response, it returns all details of the created scenario including its blueprint.

### Query parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `cols[]` | array[Enum<values>] |  | Specifies columns that are returned in the response. Use the `cols[]` parameter for every column that you want to return in the response. For example `GET /endpoint?cols[]=key1&cols[]=key2` to get both `key1` and `key2` columns in the response. Check the "Filtering" section for a full example. |  |
| `confirmed` | boolean |  | If set to true this parameter confirms the scenario creation when the scenario contains the app that is used in the organization for the first time and needs installation. If the parameter is missing or it is set to false an error code is returned and the scenario is not created. | `true` |

### Request Body

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `blueprint` | string | **Required** | The scenario blueprint. To save resources, the blueprint is sent as a string, not as an object. | See example below |
| `teamId` | integer | **Required** | The unique ID of the team in which the scenario will be created. | `1` |
| `scheduling` | string | **Required** | The scenario scheduling details. To save resources, the scheduling details are sent as a string, not as an object. | `{"type": "indefinitely", "interval": 900}` |
| `folderId` | integer |  | The unique ID of the folder in which you want to store created scenario. | `1` |
| `basedon` | integer |  | Defines if the scenario is created based on a template. The value is the template ID. | `20` |

**Example `blueprint` Value:**

```json
{
 "name": "Empty integration",
 "flow": [
  {
   "id": 2,
   "module": "json:ParseJSON",
   "version": 1,
   "metadata": {
    "designer": {
     "x": -46,
     "y": 47,
     "messages": [
      {
       "category": "last",
       "severity": "warning",
       "message": "A transformer should not be the last module in the route."
      }
     ]
    }
   }
  }
 ],
 "metadata": {
  "version": 1,
  "scenario": {
   "roundtrips": 1,
   "maxErrors": 3,
   "autoCommit": true,
   "autoCommitTriggerLast": true,
   "sequential": false,
   "confidential": false,
   "dataloss": false,
   "dlq": false,
   "freshVariables": false
  },
  "designer": {
   "orphans": []
  }
 }
}
```

### Request Example (PHP http1)

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios');
$request->setMethod(HTTP_METH_POST);

$request->setQueryData([
  'confirmed' => 'true'
]);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

$request->setBody('{"blueprint":"{ \\"name\\": \\"Empty integration\\", \\"flow\\": [ { \\"id\\": 2, \\"module\\": \\"json:ParseJSON\\", \\"version\\": 1, \\"metadata\\": { \\"designer\\": { \\"x\\": -46, \\"y\\": 47, \\"messages\\": [ { \\"category\\": \\"last\\", \\"severity\\": \\"warning\\", \\"message\\": \\"A transformer should not be the last module in the route.\\" } ] } } } ], \\"metadata\\": { \\"version\\": 1, \\"scenario\\": { \\"roundtrips\\": 1, \\"maxErrors\\": 3, \\"autoCommit\\": true, \\"autoCommitTriggerLast\\": true, \\"sequential\\": false, \\"confidential\\": false, \\"dataloss\\": false, \\"dlq\\": false, \\"freshVariables\\": false }, \\"designer\\": { \\"orphans\\": [ ] } } }","teamId":1,"scheduling":"{ \\"type\\": \\"indefinitely\\", \\"interval\\": 900 }","folderId":1,"basedon":20}');

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "scenario": {
    "id": 925,
    "name": "New scenario",
    "teamId": 215,
    "hookId": null,
    "deviceId": null,
    "deviceScope": null,
    "concept": false,
    "description": "",
    "folderId": null,
    "isinvalid": false,
    "islinked": false,
    "islocked": false,
    "isPaused": false,
    "usedPackages": [
      "json"
    ],
    "lastEdit": "2021-09-22T06:40:56.692Z",
    "scheduling": {
      "type": "indefinitely",
      "interval": 900
    },
    "iswaiting": false,
    "dlqCount": 0,
    "createdByUser": {
      "id": 985,
      "name": "John Doe",
      "email": "j.doe@example.com"
    },
    "updatedByUser": {
      "id": 986,
      "name": "John Foo",
      "email": "j.foo@example.com"
    },
    "nextExec": "2021-09-22T06:41:56.692Z",
    "created": "2021-10-22T06:41:56.692Z"
  }
}
```

---
## Get scenario details

**Method & Endpoint:** `GET /scenarios/{scenarioId}`

**Required Scopes:** `scenarios:read`

**Description:** Retrieves all available properties of a scenario with a given ID. The returned details do not include a scenario blueprint. If you want to get a scenario blueprint, refer to the Get scenario blueprint endpoint.

### Path parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `scenarioId` | integer | **Required** | The ID of the scenario. You can get the `scenarioId` with the List scenarios API call. | `112` |

### Query parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `cols[]` | array[Enum<values>] |  | Specifies columns that are returned in the response. Use the `cols[]` parameter for every column that you want to return in the response. For example `GET /endpoint?cols[]=key1&cols[]=key2` to get both `key1` and `key2` columns in the response. Check the "Filtering" section for a full example. |  |

### Request Example (PHP http1)

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios/112');
$request->setMethod(HTTP_METH_GET);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "scenario": {
    "id": 925,
    "name": "New scenario",
    "teamId": 215,
    "hookId": null,
    "deviceId": null,
    "deviceScope": null,
    "concept": false,
    "description": "",
    "folderId": null,
    "isinvalid": false,
    "islinked": false,
    "islocked": false,
    "isPaused": false,
    "usedPackages": [
      "json"
    ],
    "lastEdit": "2021-09-22T06:40:56.692Z",
    "scheduling": {
      "type": "indefinitely",
      "interval": 900
    },
    "iswaiting": false,
    "dlqCount": 0,
    "createdByUser": {
      "id": 985,
      "name": "John Doe",
      "email": "j.doe@example.com"
    },
    "updatedByUser": {
      "id": 986,
      "name": "John Foo",
      "email": "j.foo@example.com"
    },
    "nextExec": "2021-09-22T06:41:56.692Z",
    "created": "2021-10-22T06:41:56.692Z"
  }
}
```

---
## Update scenario

**Method & Endpoint:** `PATCH /scenarios/{scenarioId}`

**Required Scopes:** `scenarios:write`

**Description:** Updates a scenario with a given ID by passing new values in the request body. Any property that is not provided will be left unchanged. In the response, it returns all details of the updated scenario including properties that were not changed.

### Path parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `scenarioId` | integer | **Required** | The ID of the scenario. You can get the `scenarioId` with the List scenarios API call. | `112` |

### Query parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `cols[]` | array[Enum<values>] |  | Specifies columns that are returned in the response. Use the `cols[]` parameter for every column that you want to return in the response. For example `GET /endpoint?cols[]=key1&cols[]=key2` to get both `key1` and `key2` columns in the response. Check the "Filtering" section for a full example. |  |
| `confirmed` | boolean |  | If set to true this parameter confirms the scenario update when the scenario contains the app that is used in the organization for the first time and needs installation. If the parameter is missing or it is set to false an error code is returned and the scenario is not updated. | `true` |

### Request Body

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `blueprint` | string |  | The scenario blueprint. To save resources, the blueprint is sent as a string, not as an object. | See example below |
| `scheduling` | string |  | The scenario scheduling details. To save resources, the scheduling details are sent as a string, not as an object. | `{"type": "indefinitely", "interval": 900}` |
| `folderId` | integer |  | The unique ID of the folder in which you want to store created scenario. | `1` |
| `name` | string |  | A new name of the scenario. The name does not need to be unique. | `My New Integration` |

**Example `blueprint` Value:**

```json
{
 "name": "Empty integration",
 "flow": [
  {
   "id": 2,
   "module": "json:ParseJSON",
   "version": 1,
   "metadata": {
    "designer": {
     "x": -46,
     "y": 47,
     "messages": [
      {
       "category": "last",
       "severity": "warning",
       "message": "A transformer should not be the last module in the route."
      }
     ]
    }
   }
  }
 ],
 "metadata": {
  "version": 1,
  "scenario": {
   "roundtrips": 1,
   "maxErrors": 3,
   "autoCommit": true,
   "autoCommitTriggerLast": true,
   "sequential": false,
   "confidential": false,
   "dataloss": false,
   "dlq": false,
   "freshVariables": false
  },
  "designer": {
   "orphans": []
  }
 }
}
```

### Request Example (PHP http1)

```php
<?php

HttpRequest::methodRegister('PATCH');
$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios/112');
$request->setMethod(HttpRequest::HTTP_METH_PATCH);

$request->setQueryData([
  'confirmed' => 'true'
]);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

$request->setBody('{"blueprint":"{ \\"name\\": \\"Empty integration\\", \\"flow\\": [ { \\"id\\": 2, \\"module\\": \\"json:ParseJSON\\", \\"version\\": 1, \\"metadata\\": { \\"designer\\": { \\"x\\": -46, \\"y\\": 47, \\"messages\\": [ { \\"category\\": \\"last\\", \\"severity\\": \\"warning\\", \\"message\\": \\"A transformer should not be the last module in the route.\\" } ] } } } ], \\"metadata\\": { \\"version\\": 1, \\"scenario\\": { \\"roundtrips\\": 1, \\"maxErrors\\": 3, \\"autoCommit\\": true, \\"autoCommitTriggerLast\\": true, \\"sequential\\": false, \\"confidential\\": false, \\"dataloss\\": false, \\"dlq\\": false, \\"freshVariables\\": false }, \\"designer\\": { \\"orphans\\": [ ] } } }","scheduling":"{ \\"type\\": \\"indefinitely\\", \\"interval\\": 900 }","folderId":1,"name":"My New Integration"}');

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "scenario": {
    "id": 925,
    "name": "New scenario",
    "teamId": 215,
    "hookId": null,
    "deviceId": null,
    "deviceScope": null,
    "concept": false,
    "description": "",
    "folderId": null,
    "isinvalid": false,
    "islinked": false,
    "islocked": false,
    "isPaused": false,
    "usedPackages": [
      "json"
    ],
    "lastEdit": "2021-09-22T06:40:56.692Z",
    "scheduling": {
      "type": "indefinitely",
      "interval": 900
    },
    "iswaiting": false,
    "dlqCount": 0,
    "createdByUser": {
      "id": 985,
      "name": "John Doe",
      "email": "j.doe@example.com"
    },
    "updatedByUser": {
      "id": 986,
      "name": "John Foo",
      "email": "j.foo@example.com"
    },
    "nextExec": "2021-09-22T06:41:56.692Z",
    "created": "2021-10-22T06:41:56.692Z"
  }
}
```

---
## Delete scenario

**Method & Endpoint:** `DELETE /scenarios/{scenarioId}`

**Required Scopes:** `scenarios:write`

**Description:** Deletes a scenario with a given ID and returns the ID in the response.

### Path parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `scenarioId` | integer | **Required** | The ID of the scenario. You can get the `scenarioId` with the List scenarios API call. | `112` |

### Request Example (PHP http1)

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios/112');
$request->setMethod(HTTP_METH_DELETE);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "scenario": 1399
}
```

---
## Get trigger details

**Method & Endpoint:** `GET /scenarios/{scenarioId}/triggers`

**Required Scopes:** `scenarios:read`

**Description:** Retrieves properties of a trigger included in a scenario with a given ID. A trigger is a module that is able to return bundles that were newly added or updated (depending on the settings) since the last run of the scenario. An example of a trigger is a hook.

### Path parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `scenarioId` | integer | **Required** | The ID of the scenario. You can get the `scenarioId` with the List scenarios API call. | `112` |

### Request Example (PHP http1)

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios/112/triggers');
$request->setMethod(HTTP_METH_GET);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "id": 9765,
  "name": "WH1",
  "udid": "e7cq6zty4qcnq7fb83kzcdsgqniqtd5c",
  "scope": "hook",
  "queueCount": 0,
  "queueLimit": 100000,
  "typeName": "gateway-webhook",
  "type": "web",
  "flags": {},
  "url": "https://hook.make.com/e7cq6zty4qcnq7fb83kzcdsgqniqtd5c"
}
```

---
## Clone scenario

**Method & Endpoint:** `POST /scenarios/{scenarioId}/clone`

**Required Scopes:** `scenarios:write`

**Description:** Clones the specified scenario. The response contains all information about the scenario clone.

You have to know which app integrations the scenario contains. You can get a list of apps used in the scenario with the API call `GET /scenarios/{scenarioId}` in the `usedPackages` array.

If you are cloning the scenario to a different team and the scenario contains an app module, webhook or data store, you have to either:

* map the entity ID to a different entity with the correct properties. For example, you can map an app module connection to a different connection of the same app with the same scopes, or
* use the `notAnalyze` query parameter to turn off the scenario clone blueprint analysis.

When you turn off the scenario blueprint analysis you can map the entity ID to the `null` value, which omits the entity settings.

The scenario blueprint analysis makes sure that the scenario clone will work without further changes. If you turn off the scenario blueprint analysis, check the configuration of all entities in the scenario clone.

If you are cloning the scenario to a different team and the scenario contains a custom app or a custom function, which is not available for the users in the team, use the `confirmed` query parameter to confirm cloning of the scenario. Otherwise, you get an error listing the custom function that you have to create in the team.

Refer to the request body parameters description and examples for more information.

### Path parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `scenarioId` | integer | **Required** | The ID of the scenario. You can get the `scenarioId` with the List scenarios API call. | `112` |

### Query parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `organizationId` | integer | **Required** | The ID of the organization. | `11` |
| `cols[]` | array[Enum<values>] |  | Specifies columns that are returned in the response. Use the `cols[]` parameter for every column that you want to return in the response. For example `GET /endpoint?cols[]=key1&cols[]=key2` to get both `key1` and `key2` columns in the response. Check the "Filtering" section for a full example. |  |
| `confirmed` | boolean |  | If the scenario contains a custom app or a custom function, that is not available in the team, you have to set the `confirmed` parameter to true to clone the scenario. Otherwise you get an error and the scenario is not cloned. |  |
| `notAnalyze` | boolean |  | If you are cloning a scenario to a different team, you have to map the scenario entities (connections, data stores, webhooks, ...) from the original to the clone. If you cannot map all of the scenario entities, set the `notAnalyze` parameter to `true` to suppress the scenario blueprint analysis. |  |

### Request Body

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `name` | string | **Required** | The name for the scenario clone. The maximum length of the name is 120 characters. | `Scenario clone` |
| `teamId` | integer | **Required** | The ID of the team to which you want to clone the scenario. | `20030` |
| `account` | object |  | Specify pairs of original and clone connection IDs to map connections to the cloned scenario. | `{"4400": 5564, "5500": 7542}` |
| `key` | object |  | Specify pairs of original and clone key IDs to map keys to the cloned scenario. | `{"4383": 465}` |
| `hook` | object |  | Specify pairs of original and clone hook IDs to map webhooks to the cloned scenario. | `{"11899": 11900}` |
| `device` | object |  | Specify pairs of original and clone device IDs to map devices to the cloned scenario. | `{"432": 116}` |
| `udt` | object |  | Specify pairs of original and clone data structure IDs to map data structures to the cloned scenario. | `{"4130": 5698}` |
| `datastore` | object |  | Specify pairs of original and clone data store IDs to map data stores to the cloned scenario. | `{"3572": 4587}` |
| `states` | boolean | **Required** | Set to `true` to clone also states of the scenario modules, for example last scenario trigger execution. Setting to `false` resets the state information of the scenario modules in the scenario clone. | `true` |

### Request Example (PHP http1)

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios/112/clone');
$request->setMethod(HTTP_METH_POST);

$request->setQueryData([
  'organizationId' => '11'
]);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

$request->setBody('{"name":"Scenario clone","teamId":20030,"states":true,"account":{"4400":5564,"5500":7542},"key":{"4383":465},"hook":{"11899":11900},"device":{"432":116},"udt":{"4130":5698},"datastore":{"3572":4587}}');

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "id": 925,
  "name": "Scenario clone",
  "teamId": 20030,
  "hookId": 11900,
  "deviceId": 116,
  "deviceScope": null,
  "concept": false,
  "description": "",
  "folderId": null,
  "isinvalid": false,
  "islinked": false,
  "islocked": false,
  "isPaused": false,
  "usedPackages": [
    "gateway",
    "airtable",
    "datastore",
    "google-sheets",
    "util"
  ],
  "lastEdit": "2021-09-22T06:40:56.692Z",
  "scheduling": {
    "type": "indefinitely",
    "interval": 900
  },
  "iswaiting": false,
  "dlqCount": 0,
  "createdByUser": {
    "id": 985,
    "name": "John Doe",
    "email": "j.doe@example.com"
  },
  "updatedByUser": {
    "id": 986,
    "name": "John Foo",
    "email": "j.foo@example.com"
  },
  "nextExec": "2021-09-22T06:41:56.692Z"
}
```

---
## Activate scenario

**Method & Endpoint:** `POST /scenarios/{scenarioId}/start`

**Required Scopes:** `scenarios:write`

**Description:** Activates the specified scenario. Also executes the scenario if the scenario is scheduled to run at regular intervals. Read more about scenario scheduling.

The API call response contains the scenario ID and the scenario `isLinked` property set to `true`.

### Path parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `scenarioId` | integer | **Required** | The ID of the scenario. You can get the `scenarioId` with the List scenarios API call. | `112` |

### Request Example (PHP http1)

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://eu1.make.com/api/v2/scenarios/112/start');
$request->setMethod(HTTP_METH_POST);

$request->setHeaders([
  'Authorization' => 'Token abcdefab-1234-5678-abcd-112233445566'
]);

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

### Response Schema

```json
{
  "scenario": {
    "id": 5,
    "islinked": true
  }
}
```

---
## Run a scenario

**Method & Endpoint:** `POST /scenarios/{scenarioId}/run`

**Required Scopes:** `scenarios:read`, `scenarios:write`, `scenarios:run`

**Description:** Runs the specified scenario. The scenario has to be active. If your scenario has required scenario inputs you have to provide the scenario inputs in the request body.

### Path parameters

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `scenarioId` | integer | **Required** | The ID of the scenario. Get the ID of the scenario with the API call `GET /scenarios`. | `111` |

### Request Body

| Parameter | Type | Required | Description | Example |
|---|---|---|---|---|
| `data` | object |  | If your scenario has inputs specify the input parameters and values in the `data` object. | `{"Test input": "Test value", "My array": ["test 1", "test 2"], "My collection": {"key": "value"}}` |
| `responsive` | boolean |  | If set to `true` the Make API waits until the scenario finishes. The response contains the scenario status and `executionId`. If the scenario execution takes longer than 40 seconds, the API call returns the time out error, but
