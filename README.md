## [Alpha] Sculping Contentful Bundle

Using Contentful as content management system (CMS), it will download the content from contentful and add them on `source/_[something]` directory.

### How to use

#### Environment variables:

```
contentful_token=<token> # This is the access token for this space. Normally you get both ID and the token in the Contentful web app
contentful_space_id=<space_id> # This is the space ID. A space is like a project folder in Contentful terms
```

#### Installation:

`composer require technikh/sculpin-fb-bundle dev-master`

#### Usage

On `app/SculpinKernel.php` you should add:

```php
    // ...
    protected function getAdditionalSculpinBundles(): array
    {
        return [
            SculpinFacebookBundle::class,
        ];
    }
    // ...
```

```bash
# Environment variables
$ export contentful_token=<token>
$ export contentful_space_id=<space_id>

# Sculpin command
$ vendor/bin/sculpin contentful:fetch
Created file: source/_til/2021-04-05-first-post.md
Created file: source/_til/2020-12-05-second-post.md
Created file: source/_til/2020-11-23-third-post.md
```

#### On Contentful:
* The name of the content type is the name that we create the folder inside source;
* The fields `language, title, date and contentMarkdown` must exist;


> We are still going to add the settings to the user to use their specific fields on this bundle 


