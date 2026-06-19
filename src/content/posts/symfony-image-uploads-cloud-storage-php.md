---
title: "Symfony Image Uploads With Cloud Object Storage"
description: "Learn Symfony image uploads to AWS S3 and DigitalOcean Spaces using Flysystem. Step-by-step guide for cloud static object storage with PHP 8.x."
pubDate: "2023-07-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Symfony", "Image Uploads", "AWS S3", "Cloud Storage", "Flysystem", "PHP", "Object Storage", "DigitalOcean Spaces"]
```

Storing uploaded files on the local filesystem worked when applications ran on a single server. Modern PHP applications deploy across multiple servers, use auto-scaling groups, and run in containerized environments. Local file storage breaks in all these scenarios.

Cloud object storage solves this. Services like AWS S3 and DigitalOcean Spaces provide cheap, scalable, and highly available storage accessible from any server. Combined with Flysystem's abstraction layer, swapping between local and cloud storage becomes a configuration change.

## What You'll Learn

- Setting up AWS S3 client for PHP with credentials and configuration
- Basic S3 operations: put, get, delete, and list objects
- Integrating Flysystem for storage abstraction
- Configuring Symfony 6 for cloud object storage services
- Handling file uploads with VichUploaderBundle and Symfony forms
- Switching between local and cloud storage with environment variables
- Best practices for secure credential management
- Handling image uploads in Symfony forms with validation
- Building a Flysystem service wrapper for reusable storage operations

## AWS SDK for PHP Setup

Start by installing the AWS SDK via Composer.

```bash
composer require aws/aws-sdk-php
```

The SDK requires credentials and region configuration. The recommended approach is environment variables.

```bash
export AWS_ACCESS_KEY_ID="your-access-key"
export AWS_SECRET_ACCESS_KEY="your-secret-key"
export AWS_DEFAULT_REGION="us-east-1"
```

With environment variables set, creating an S3 client is straightforward.

```php
<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-2',
]);
```

You can also pass credentials directly, but never commit them to your repository.

```php
<?php

use Aws\Credentials\Credentials;

$credentials = new Credentials('key', 'secret');

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => 'us-west-2',
    'credentials' => $credentials,
]);
```

## Basic S3 Operations

### Uploading a File

```php
<?php

use Aws\S3\Exception\S3Exception;

try {
    $s3Client->putObject([
        'Bucket' => 'acme-bucket',
        'Key' => 'profile.jpg',
        'Body' => fopen('/path/to/profile.jpg', 'r'),
    ]);
} catch (S3Exception $e) {
    echo 'Upload failed: ' . $e->getAwsErrorMessage() . PHP_EOL;
}
```

The `Key` parameter is the object's path in the bucket. Use it to organize files in a directory-like structure.

### Deleting a File

```php
<?php

try {
    $s3Client->deleteObject([
        'Bucket' => 'acme-bucket',
        'Key' => 'profile.jpg',
    ]);
} catch (S3Exception $e) {
    echo 'Delete failed: ' . $e->getAwsErrorMessage() . PHP_EOL;
}
```

### Listing Bucket Contents

```php
<?php

try {
    $contents = $s3Client->listObjects([
        'Bucket' => 'acme-bucket',
        'Prefix' => 'path/to/',
    ]);

    foreach ($contents['Contents'] as $content) {
        echo $content['Key'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
```

## Flysystem Abstraction Layer

Direct S3 usage works but couples your code to AWS. Flysystem provides a storage abstraction that lets you switch between local filesystem, S3, Spaces, or in-memory storage without code changes.

```bash
composer require league/flysystem-bundle
composer require league/flysystem-aws-s3-v3
composer require aws/aws-sdk-php
```

### Understanding Flysystem's Architecture

Flysystem 3 introduces a clean separation of concerns. The `FilesystemOperator` interface defines all file operations. Adapters implement the interface for specific storage backends. The `league/flysystem-bundle` integrates this with Symfony's dependency injection container.

The bundle mounts multiple adapters under a single filesystem. You can define separate adapters for different storage locations (user photos, documents, temporary files) and switch each independently between local and cloud storage.

### Bucket Configuration per Environment

Use environment variables to configure different buckets for development, staging, and production. This prevents accidentally writing test data to the production bucket:

```yaml
services:
  acme.storage.aws:
    class: League\Flysystem\AwsS3V3\AwsS3V3Adapter
    arguments:
      - '@acme.s3_client'
      - "%env(AWS_STORAGE_BUCKET)%"
```

```env
# .env.dev
AWS_STORAGE_BUCKET="myapp-dev-uploads"

# .env.prod
AWS_STORAGE_BUCKET="myapp-prod-uploads"
```

### Environment Configuration

Set environment variables in `.env.dev.local` (never commit these):

```env
AWS_ACCESS_KEY="ABC123"
AWS_SECRET_KEY="abcxyz123"
AWS_REGION="us-east-1"
AWS_STORAGE_BUCKET="acme-bucket"
FLYSYSTEM_ADAPTER_STORAGE="acme.storage.aws"
```

Configure the S3 client in `config/services.yaml`:

```yaml
services:
  acme.s3_client:
    class: Aws\S3\S3Client
    arguments:
      -
        version: '2006-03-01'
        region: "%env(AWS_REGION)%"
        use_aws_shared_config_files: false
        credentials:
          key: "%env(AWS_ACCESS_KEY)%"
          secret: "%env(AWS_SECRET_KEY)%"
```

Configure storage adapters:

```yaml
services:
  acme.storage.aws:
    class: League\Flysystem\AwsS3V3\AwsS3V3Adapter
    arguments:
      - '@acme.s3_client'
      - "%env(AWS_STORAGE_BUCKET)%"

  acme.storage.local:
    class: League\Flysystem\Local\LocalFilesystemAdapter
    arguments:
      - '%kernel.project_dir%/var/storage'

  acme.storage:
    class: League\Flysystem\Filesystem
    factory: 'League\Flysystem\Filesystem::mount'
    arguments:
      -
        acme.storage.aws: '@acme.storage.aws'
        acme.storage.local: '@acme.storage.local'
```

### Flysystem Service Wrapper

Create a service class to encapsulate storage operations.

```php
<?php

namespace App\Service\Utility;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

class FlysystemService
{
    private array $storages;

    public function __construct(
        FilesystemOperator $acmeStorage,
    ) {
        $this->storages['user_profile_pictures'] = $acmeStorage;
    }

    public function getStorage(string $key): FilesystemOperator
    {
        return $this->storages[$key];
    }

    public function listContents(string $key): array
    {
        return $this->storages[$key]
            ->listContents('/')
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
            ->map(fn(StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    public function delete(string $key, string $filename): void
    {
        $this->storages[$key]->delete($filename);
    }
}
```

## VichUploaderBundle Integration

VichUploaderBundle handles file uploads and integrates with Flysystem.

```bash
composer require vich/uploader-bundle
```

Configure VichUploader in `config/packages/vich_uploader.yaml`:

```yaml
vich_uploader:
  db_driver: orm
  metadata:
    type: attribute
  mappings:
    user_profile_pictures:
      uri_prefix: /images/profiles
      upload_destination: '%env(FLYSYSTEM_ADAPTER_STORAGE)%'
      namer: Vich\UploaderBundle\Naming\UniqidNamer
```

## Symfony Form for Image Upload

```php
<?php

// src/Form/UserFormType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ... other fields
            ->add('imageFile', FileType::class, [
                'label' => 'Profile Picture',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '500k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/gif',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (jpg, png, gif).',
                    ]),
                ],
            ]);
    }
}
```

## Controller Handling Upload

```php
<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController
{
    public function editUser(?int $id, Request $request): Response
    {
        $user = $this->userRepository->findOneBy(['id' => $id]) ?? new User();

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            $user->setImageFile($imageFile);
            // Save the entity
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

### Setting Up Pre-Signed URLs

For private files, generate temporary URLs that grant access for a limited time:

```php
<?php

use Aws\S3\S3Client;

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-1',
]);

$cmd = $s3Client->getCommand('GetObject', [
    'Bucket' => 'acme-bucket',
    'Key' => 'private/document.pdf',
]);

$request = $s3Client->createPresignedRequest($cmd, '+20 minutes');
$url = (string) $request->getUri();

// Share $url with the authorized user
```

Pre-signed URLs are secure because they are temporary and scoped to a specific object and operation.

### Image Resizing After Upload

Automatically resize uploaded images before storing them in the cloud. This saves bandwidth and storage costs.

```php
<?php

declare(strict_types=1);

use Intervention\Image\ImageManager;

final class ImageProcessor
{
    public function __construct(
        private ImageManager $manager,
        private FlysystemService $storage,
    ) {}

    public function processAndStore(
        string $sourcePath,
        string $filename,
        int $maxWidth = 1200,
    ): void {
        $image = $this->manager->make($sourcePath);

        if ($image->width() > $maxWidth) {
            $image->resize($maxWidth, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $stream = $image->stream('jpg', 85);
        $this->storage->getStorage('user_profile_pictures')
            ->write($filename, $stream->getContents());
    }
}
```

### URL Generation for Stored Objects

Once files are in S3, you need to generate URLs for users to view or download them.

```php
<?php

use Aws\S3\S3Client;

function getFileUrl(string $bucket, string $key, bool $public = true): string
{
    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
    ]);

    if ($public) {
        return $s3Client->getObjectUrl($bucket, $key);
    }

    // Generate pre-signed URL for private files
    $cmd = $s3Client->getCommand('GetObject', [
        'Bucket' => $bucket,
        'Key' => $key,
    ]);

    $request = $s3Client->createPresignedRequest($cmd, '+1 hour');
    return (string) $request->getUri();
}
```

For public files, the URL contains the bucket's regional endpoint. For private files, the pre-signed URL includes authentication parameters that expire after the specified duration.

## Switching Storage Providers

The entire configuration supports toggling between local and cloud storage by changing a single environment variable.

```env
# For local development
FLYSYSTEM_ADAPTER_STORAGE="acme.storage.local"

# For production
FLYSYSTEM_ADAPTER_STORAGE="acme.storage.aws"
```

No code changes needed. Flysystem abstracts the differences.

## Real-World Use Cases

### User Profile Pictures

Profile photo uploads with automatic resizing and CDN delivery through S3's integration with CloudFront.

### Document Management Systems

PDF uploads, contract storage, and document archival. S3 lifecycle policies automatically move old documents to Glacier for cost savings.

### E-Commerce Product Images

Product catalogs with thousands of images. S3 stores originals, and Lambda functions generate thumbnails at multiple resolutions.

### Media Libraries

Video, audio, and image uploads. Cloud storage ensures files survive server replacements and are available from any region.

## Best Practices

- **Never commit credentials** - Use environment variables or AWS IAM roles for EC2. Keep secrets out of version control.
- **Validate uploads server-side** - Client-side validation is easily bypassed. Always validate file types and sizes on the server.
- **Use unique filenames** - UniqidNamer or UUID-based names prevent collisions and path-traversal attacks.
- **Set bucket policies** - Restrict public access to only what is necessary. Use pre-signed URLs for private files.
- **Enable versioning** - S3 versioning protects against accidental deletion. Combine with lifecycle policies for cost management.
- **Monitor costs** - Cloud storage bills by storage volume and data transfer. Monitor usage to avoid surprises.

## Common Mistakes to Avoid

- **Storing files locally in containerized environments** - Containers are ephemeral. Files written to disk disappear on restart.
- **Allowing arbitrary file uploads** - Without MIME type validation and size limits, users can upload malicious files.
- **Using sequential filenames** - Sequential IDs make it trivial to enumerate all user files.
- **Forgetting CORS configuration** - Direct browser uploads to S3 require CORS policy configuration on the bucket.
- **Hardcoding bucket names** - Use environment variables or configuration. Different environments use different buckets.

## Frequently Asked Questions

### Why use Flysystem instead of direct S3 SDK calls?

Flysystem provides abstraction. You can use local storage in development and S3 in production. Switching providers becomes a configuration change, not a code rewrite.

### Does Flysystem support DigitalOcean Spaces?

Yes. Spaces is S3-compatible. Use the `league/flysystem-aws-s3-v3` adapter with Spaces endpoint and region configuration.

### How do I serve uploaded images to users?

For public files, use the bucket's public URL or a CDN like CloudFront. For private files, generate pre-signed URLs with expiration times.

### What happens when I delete a user's profile picture?

Delete the S3 object using the Flysystem `delete()` method. Optionally, implement a soft-delete with S3 versioning for recovery.

### Can I resize images after upload?

Yes. Use a service like Imagine or Intervention Image before storing, or configure an AWS Lambda function to resize on upload.

### How do I migrate existing local files to S3?

Write a console command that reads local files, uploads them to S3 via Flysystem, and updates the database file paths.

### What about file permissions and ACLs?

S3 bucket policies and object ACLs control access. For most applications, bucket-level policies with pre-signed URLs provide sufficient security.

## Conclusion

Cloud object storage is essential for modern PHP applications. Symfony combined with Flysystem and the AWS SDK provides a robust, flexible solution for handling file uploads.

The abstraction layer means you can develop locally without S3, test with in-memory storage, and deploy to S3 or Spaces in production. Your application becomes more scalable, resilient, and deployment-agnostic.

Start migrating your file storage to the cloud today. Your future self, scaling your application to multiple servers, will thank you.
