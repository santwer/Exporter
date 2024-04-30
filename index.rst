=====================
Word Template Exporter
=====================

Package for easy Word exports in Laravel on given Templates.
This package is based on `phpoffice/phpword <https://github.com/PHPOffice/PHPWord>`_.

.. image:: https://img.shields.io/github/commit-activity/m/santwer/exporter
   :alt: Commit Activity
   :target: https://github.com/santwer/exporter

.. image:: https://img.shields.io/packagist/dt/santwer/exporter
   :alt: Total Downloads
   :target: https://packagist.org/packages/santwer/exporter

.. image:: https://img.shields.io/packagist/v/santwer/exporter
   :alt: Latest Stable Version
   :target: https://packagist.org/packages/santwer/exporter

.. image:: https://img.shields.io/packagist/l/santwer/exporter
   :alt: License
   :target: https://packagist.org/packages/santwer/exporter

.. contents:: Table of Contents
   :local:

Installation
============

Exporter is installed via `Composer <https://getcomposer.org/>`_.
To add a dependency to Exporter in your project, either

Run the following to use the latest stable version

.. code-block:: bash

    composer require santwer/exporter

or if you want the latest master version

.. code-block:: bash

    composer require santwer/exporter:dev-master

You can of course also manually edit your composer.json file

.. code-block:: json

    {
        "require": {
           "santwer/exporter": "v0.3.*"
        }
    }

Configuration (optional)
------------------------

To use pdf export it is needed to install LibreOffice. WordTemplateExporter is using the soffice command to convert the docx file to pdf.

.. code-block:: bash

    sudo apt-get install libreoffice

Windows
^^^^^^^

Download and install LibreOffice from `here <https://www.libreoffice.org/download/download/>`_
Also add the path to the soffice command to the system environment variables.

.. code-block:: bash

    export PATH=$PATH:/path/to/soffice

How to use with ExportClasses
=============================

Usage
-----

You can use the WordExporter Facade as follows. The format of the exported file is determined by the file extension. Supported formats are .docx and .pdf.

.. code-block:: php

    use WordExporter\Facades\WordExporter;

    // Download as a Word file
    WordExporter::download(new MyExportClass(), 'final-word.docx');

    // Store the exported file
    WordExporter::store(new MyExportClass(), 'path/to/save/export.docx');

    // Store the exported file with an certain filename
    WordExporter::storeAs(new MyExportClass(), 'path/to/save/', 'export.docx');

    // Store the exported file with an certain filename as a batch
    WordExporter::batchStore(
        new Exportable(new MyExportClass(), 'path/to/save/', 'export.docx'),
        new Exportable(new MyExportClass1(), 'path/to/save/', 'export1.docx'),
        new Exportable(new MyExportClass2(), 'path/to/save/', 'export2.pdf'),
        );

    // Queue it for later processing
    WordExporter::queue(new MyExportClass(), 'path/to/save/export.docx');

Creating a New Export
---------------------

You can create a new export using the following Artisan command:

.. code-block:: bash

    php artisan make:word {className}

Replace {className} with the name of the new export class.

Interfaces
^^^^^^^^^^

The object $export can be implemented with the following interfaces:

.. list-table::
   :header-rows: 1

   * - Interface
     - Description
     - Example
   * - `FromWordTemplate`
     - Required. Interface indicating the export is from a Word template.
     - `class MyExportClass implements FromWordTemplate`
   * - `GlobalTokens`
     - Interface for providing global tokens for replacement in Word template.
     - `class MyGlobalTokens implements GlobalTokens`
   * - `TokensFromArray`
     - Interface for providing tokens from an array for replacement in Word template.
     - `class MyArrayTokens implements TokensFromArray`
   * - `TokensFromCollection`
     - Interface for providing tokens from a collection for replacement in Word template.
     - `class MyCollectionTokens implements TokensFromCollection`
   * - `TokensArray`
     - Interface for providing tokens from an array without any block data
     -
   * - `TokensFromObject`
     - Interface for providing tokens from an object/class without any block data
     -
   * - `TokensFromModel`
     - Interface for prodiding tokens from a model without any block data
     -
   * - `WithCharts`
     - Interface that allows you to replace text charts as array
     -
   * - `WithCheckboxes`
     - Interfaces that allows you to replace text with Checkboxes, either checked or not checked
     -
   * - `WithImages`
     - Interface that allows you to replace text with Images
     -

Each of these interfaces defines methods that need to be implemented according to the specific requirements of the export process. These methods typically involve returning an array of key-value pairs where keys represent placeholders in the Word template and values are the data to replace those placeholders with.

Example
-------

Word file:

.. code-block:: text

    ${TownDateFormat}


    ${customer}
        ${name}, ${email}
        ${deliveryAddress.street}, ${deliveryAddress.city} ${deliveryAddress.postcode}
    ${/customer}

Controller:

.. code-block:: php

    namespace App\Http\Controllers;

    use App\Http\Export\FirstExport;
    use Santwer\Exporter\Facade\WordExport;

    class HomeController extends Controller
    {
        public function index()
        {
            return WordExport::download(new FirstExport(), 'myExport.docx');
        }
    }

Export Class:

.. code-block:: php

    namespace App\Http\Export;

    use Santwer\Exporter\Concerns\FromWordTemplate;
    use Santwer\Exporter\Concerns\GlobalTokens;
    use Santwer\Exporter\Concerns\TokensFromCollection;
    use Illuminate\Support\Collection;

    class FirstExport implements FromWordTemplate, TokensFromCollection, GlobalTokens
    {
     public function items(): Collection
     {
      return collect([
       [
        'name' => 'Jane Smith',
        'email' => 'jane.smith@example.com',
        'deliveryAddress' => [
         'street' => 'Main Street',
         'city' => 'Metropolis',
         'postcode' => '543210',
        ],
       ],
       [
        'name' => 'Alice Johnson',
        'email' => 'alice.johnson@example.com',
        'deliveryAddress' => [
         'street' => 'Elm Street',
         'city' => 'Springfield',
         'postcode' => '987654',
        ],
       ],
       [
        'name' => 'Bob Williams',
        'email' => 'bob.williams@example.com',
        'deliveryAddress' => [
         'street' => 'Oak Avenue',
         'city' => 'Townsville',
         'postcode' => '135792',
        ],
       ],
      ]);
     }

     public function blockName():string
     {
      return 'customer';
     }

     public function values(): array
     {
      return [
       'TownDateFormat' => 'Townsville, '. now()->format('Y-m-d'),
      ];
     }

     public function itemTokens($item) : array
     {
      return $item;
     }

     public function wordTemplateFile(): string
     {
      return 'uploads/myDocFile.docx';
     }
    }

