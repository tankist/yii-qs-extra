Yii QsExtra Extensions
======================

Yii QsExtra Extensions contains advanced extensions for [Yii Framework](https://github.com/yiisoft/yii)
developed and used in [QuartSoft](http://quartsoft.com).


REQUIREMENTS
------------

Most of the extensions in this pack require 'Yii Qs Extensions'
already added to the project.


INSTALLATION
------------

Generally you may place the content of this repository anywhere.
For the consistency 'protected/extensions/qsextra' is recommended.
To enable the usage of the extensions to must specify alias 'qsextra'
as well as 'qs' in your Yii application configuration to be pointing
to 'lib' directory (protected/extensions/qs.lib), like following:

      // Yii application configuration:
      return array(
          'aliases' => array(
              'qs' => 'ext.qs.lib',
              'qsextra' => 'ext.qsextra.lib',
              ...
          ),
          ...
      )

Note: many of the extensions are heavy in disk space consuming,
so may consider to add only selected files from this pack.