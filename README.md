# com.axe.client

### Installation

1. in composer.json add following:
   ```json
      "repositories": [
          {
              "type": "github",
              "url": "https://github.com/remote-tech/com.axe.client.git"
          }
      ]
   ```
   ```json
       "extra": {
        "symfony": {
            "allow-contrib": false,
            "require": "<your sf version>",
            "endpoint": [
                "https://api.github.com/repos/remote-tech/com.axe.client/contents/index.json",
                "flex://defaults"
            ]
        }
    },
   ```
2. Run:
   ```php 
       composer require "remote-tech/com.axe.client": "v1.7"
      ``` 
  
3. run the receipe that will generate a config file in ```config/packages/remote_tech_axe_oauth.yaml```


4. add ENV params 
   ```dotenv
      AUTHAPI_HOST=
      AUTHAPI_CLIENT_ID=
      AUTHAPI_CLIENT_SECRET=
   ```

### Default Doctrine User entity + User provider usage
   if you want to use the User entity provided by the lib in ```security.yaml``` add the following:
   
   set the user provider
   ```yaml
      providers:
         rt_user_provider:
            id: 'RemoteTech\ComAxe\Client\Oauth\AuthUserProvider'
   ```

   add to the firewall config section
   ```yaml
      main:
         ...
         provider: rt_user_provider
         custom_authenticators:
             - RemoteTech\ComAxe\Client\Oauth\AuthAuthenticator
         entry_point: RemoteTech\ComAxe\Client\Oauth\AuthAuthenticator
         logout:
             path: app_logout_client
         ...
   ```

### Custom User provider usage
   If you want a custom User Provider define your own 
   User Provider that implements ```RemoteTech\ComAxe\Client\Oauth\UserProvider\AxeUserProviderInterface```
   
   In ```config/packages/remote_tech_axe_oauth.yaml``` register your User provider 
   and remove the ```doctrine``` config

   ```yaml
    # configure the user provider
    RemoteTech\ComAxe\Client\Oauth\UserProvider\AxeUserProviderInterface:
        alias: <your custom provider>
  ```

### SSO Global Logout
   In order to globally logout from Oauth2 Server 
   implement a controller like:
   
```php
    public function index(RemoteTech\ComAxe\Client\Oauth\AuthService $service): Response
    {
        $url = $service->generateLogoutUrl(
            <your app logout url>
        );

        $response = new RedirectResponse($url);
        $response->headers->add(
            ['Authorization' => 'Bearer ' . <the_access_token>]
        );
        return $response;
    }
```