<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;

// Add the tempstore equivalent of the display set up in
// ../layout-builder-enable.php but with a different layout.
Database::getConnection()->insert('key_value_expire')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'tempstore.shared.layout_builder.section_storage.overrides',
    'name' => 'node.1',
    'value' => 'O:8:"stdClass":3:{s:4:"data";a:1:{s:15:"section_storage";O:67:"Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage":9:{s:10:" * context";a:2:{s:6:"entity";O:40:"Drupal\Core\Plugin\Context\EntityContext":6:{s:14:" * contextData";O:48:"Drupal\Core\Entity\Plugin\DataType\EntityAdapter":4:{s:9:" * entity";O:23:"Drupal\node\Entity\Node":29:{s:10:"in_preview";N;s:9:" * values";a:28:{s:3:"nid";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:3:"vid";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"2";}}}s:4:"type";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:9:"target_id";s:7:"article";}}}s:4:"uuid";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:36:"0b164728-be64-41b3-b610-16be2ec381ef";}}}s:8:"langcode";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:2:"en";}}}s:12:"revision_uid";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:9:"target_id";s:1:"1";}}}s:18:"revision_timestamp";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:10:"1439730369";}}}s:12:"revision_log";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:0:"";}}}s:16:"revision_default";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:17:"isDefaultRevision";a:1:{s:9:"x-default";s:1:"1";}s:6:"status";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:3:"uid";a:1:{s:9:"x-default";a:1:{i:0;a:4:{s:9:"target_id";s:1:"1";s:11:"_attributes";a:1:{s:8:"property";a:1:{i:0;s:13:"schema:author";}}s:7:"_loaded";b:1;s:19:"_accessCacheability";O:35:"Drupal\Core\Cache\CacheableMetadata":3:{s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;}}}}s:5:"title";a:1:{s:9:"x-default";a:1:{i:0;a:2:{s:5:"value";s:24:"Test Article - New title";s:11:"_attributes";a:1:{s:8:"property";a:1:{i:0;s:11:"schema:name";}}}}}s:7:"created";a:1:{s:9:"x-default";a:1:{i:0;a:2:{s:5:"value";s:10:"1439730300";s:11:"_attributes";a:2:{s:8:"property";a:1:{i:0;s:18:"schema:dateCreated";}s:7:"content";s:25:"2015-08-16T13:05:00+00:00";}}}}s:7:"changed";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:10:"1439730369";}}}s:7:"promote";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:6:"sticky";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:16:"default_langcode";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:29:"revision_translation_affected";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:26:"content_translation_source";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:3:"und";}}}s:28:"content_translation_outdated";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"0";}}}s:4:"body";a:1:{s:9:"x-default";a:1:{i:0;a:4:{s:5:"value";s:4:"Body";s:7:"summary";s:0:"";s:6:"format";s:10:"basic_html";s:11:"_attributes";a:1:{s:8:"property";a:1:{i:0;s:11:"schema:text";}}}}}s:7:"comment";a:1:{s:9:"x-default";a:1:{i:0;a:7:{s:6:"status";s:1:"2";s:3:"cid";s:1:"0";s:22:"last_comment_timestamp";s:10:"1439730300";s:17:"last_comment_name";N;s:16:"last_comment_uid";s:1:"1";s:13:"comment_count";s:1:"0";s:11:"_attributes";a:1:{s:3:"rel";a:1:{i:0;s:14:"schema:comment";}}}}}s:11:"field_image";a:1:{s:9:"x-default";a:1:{i:0;a:7:{s:9:"target_id";s:1:"3";s:3:"alt";s:3:"Alt";s:5:"title";s:0:"";s:5:"width";s:3:"175";s:6:"height";s:3:"200";s:7:"_loaded";b:1;s:19:"_accessCacheability";O:35:"Drupal\Core\Cache\CacheableMetadata":3:{s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;}}}}s:10:"field_tags";a:1:{s:9:"x-default";a:1:{i:0;a:3:{s:9:"target_id";s:1:"5";s:7:"_loaded";b:1;s:19:"_accessCacheability";O:35:"Drupal\Core\Cache\CacheableMetadata":3:{s:16:" * cacheContexts";a:1:{i:0;s:16:"user.permissions";}s:12:" * cacheTags";a:1:{i:0;s:15:"taxonomy_term:5";}s:14:" * cacheMaxAge";i:-1;}}}}s:22:"layout_builder__layout";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:7:"section";O:29:"Drupal\layout_builder\Section":3:{s:11:" * layoutId";s:21:"layout_twocol_section";s:17:" * layoutSettings";a:0:{}s:13:" * components";a:1:{s:9:"some-uuid";O:38:"Drupal\layout_builder\SectionComponent":5:{s:7:" * uuid";s:9:"some-uuid";s:9:" * region";s:5:"first";s:16:" * configuration";a:6:{s:2:"id";s:29:"field_block:node:article:body";s:5:"label";s:4:"Body";s:8:"provider";s:14:"layout_builder";s:13:"label_display";s:7:"visible";s:9:"formatter";a:4:{s:5:"label";s:5:"above";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:15:"context_mapping";a:1:{s:6:"entity";s:21:"layout_builder.entity";}}s:9:" * weight";i:0;s:13:" * additional";a:0:{}}}}}}}s:4:"book";a:17:{s:3:"nid";s:1:"1";s:3:"bid";s:1:"1";s:3:"pid";s:1:"0";s:12:"has_children";s:1:"0";s:6:"weight";s:1:"0";s:5:"depth";s:1:"1";s:2:"p1";s:1:"1";s:2:"p2";s:1:"0";s:2:"p3";s:1:"0";s:2:"p4";s:1:"0";s:2:"p5";s:1:"0";s:2:"p6";s:1:"0";s:2:"p7";s:1:"0";s:2:"p8";s:1:"0";s:2:"p9";s:1:"0";s:9:"link_path";s:6:"node/1";s:10:"link_title";s:24:"Test Article - New title";}s:4:"path";a:1:{s:9:"x-default";a:1:{i:0;a:4:{s:3:"pid";s:1:"1";s:6:"source";s:7:"/node/1";s:5:"alias";s:13:"/test-article";s:8:"langcode";s:2:"en";}}}}s:9:" * fields";a:0:{}s:19:" * fieldDefinitions";N;s:12:" * languages";N;s:14:" * langcodeKey";s:8:"langcode";s:21:" * defaultLangcodeKey";s:16:"default_langcode";s:17:" * activeLangcode";s:9:"x-default";s:18:" * defaultLangcode";s:2:"en";s:15:" * translations";a:1:{s:9:"x-default";a:1:{s:6:"status";i:1;}}s:24:" * translationInitialize";b:0;s:14:" * newRevision";b:0;s:20:" * isDefaultRevision";s:1:"1";s:13:" * entityKeys";a:4:{s:6:"bundle";s:7:"article";s:2:"id";s:1:"1";s:8:"revision";s:1:"2";s:4:"uuid";s:36:"0b164728-be64-41b3-b610-16be2ec381ef";}s:25:" * translatableEntityKeys";a:8:{s:5:"label";a:0:{}s:8:"langcode";a:1:{s:9:"x-default";s:2:"en";}s:6:"status";a:1:{s:9:"x-default";s:1:"1";}s:9:"published";a:1:{s:9:"x-default";s:1:"1";}s:3:"uid";a:0:{}s:5:"owner";a:0:{}s:16:"default_langcode";a:1:{s:9:"x-default";s:1:"1";}s:29:"revision_translation_affected";a:1:{s:9:"x-default";s:1:"1";}}s:12:" * validated";b:0;s:21:" * validationRequired";b:0;s:19:" * loadedRevisionId";s:1:"2";s:33:" * revisionTranslationAffectedKey";s:29:"revision_translation_affected";s:37:" * enforceRevisionTranslationAffected";a:0:{}s:15:" * entityTypeId";s:4:"node";s:15:" * enforceIsNew";N;s:12:" * typedData";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:12:" * isSyncing";b:0;}s:13:" * definition";O:49:"Drupal\Core\Entity\TypedData\EntityDataDefinition":1:{s:13:" * definition";a:13:{s:5:"class";s:48:"Drupal\Core\Entity\Plugin\DataType\EntityAdapter";s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Content";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"constraints";a:7:{s:13:"EntityChanged";N;s:26:"EntityUntranslatableFields";N;s:11:"BookOutline";a:0:{}s:12:"MenuSettings";a:0:{}s:36:"ContentTranslationSynchronizedFields";N;s:10:"EntityType";s:4:"node";s:7:"NotNull";a:0:{}}s:8:"internal";b:0;s:16:"definition_class";s:50:"\Drupal\Core\Entity\TypedData\EntityDataDefinition";s:10:"list_class";s:47:"\Drupal\Core\TypedData\Plugin\DataType\ItemList";s:21:"list_definition_class";s:41:"\Drupal\Core\TypedData\ListDataDefinition";s:35:"unwrap_for_canonical_representation";b:1;s:2:"id";s:6:"entity";s:11:"description";N;s:7:"deriver";s:57:"\Drupal\Core\Entity\Plugin\DataType\Deriver\EntityDeriver";s:8:"provider";s:4:"core";s:8:"required";b:1;}}s:7:" * name";N;s:9:" * parent";N;}s:20:" * contextDefinition";O:50:"Drupal\Core\Plugin\Context\EntityContextDefinition":9:{s:11:" * dataType";s:11:"entity:node";s:8:" * label";r:248;s:14:" * description";N;s:13:" * isMultiple";b:0;s:13:" * isRequired";b:1;s:15:" * defaultValue";N;s:14:" * constraints";a:0:{}s:14:" * _serviceIds";a:1:{s:16:"typedDataManager";s:18:"typed_data_manager";}s:18:" * _entityStorages";a:0:{}}s:23:" * cacheabilityMetadata";O:35:"Drupal\Core\Cache\CacheableMetadata":3:{s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:1:{i:0;s:6:"node:1";}s:14:" * cacheMaxAge";i:-1;}s:15:" * contextValue";N;s:14:" * _serviceIds";a:1:{s:16:"typedDataManager";s:18:"typed_data_manager";}s:18:" * _entityStorages";a:0:{}}s:9:"view_mode";O:34:"Drupal\Core\Plugin\Context\Context":6:{s:14:" * contextData";O:48:"Drupal\Core\TypedData\Plugin\DataType\StringData":4:{s:8:" * value";s:4:"full";s:13:" * definition";O:36:"Drupal\Core\TypedData\DataDefinition":1:{s:13:" * definition";a:5:{s:4:"type";s:6:"string";s:5:"label";N;s:11:"description";N;s:8:"required";b:1;s:11:"constraints";a:2:{s:13:"PrimitiveType";a:0:{}s:7:"NotNull";a:0:{}}}}s:7:" * name";N;s:9:" * parent";N;}s:20:" * contextDefinition";O:44:"Drupal\Core\Plugin\Context\ContextDefinition":9:{s:11:" * dataType";s:6:"string";s:8:" * label";N;s:14:" * description";N;s:13:" * isMultiple";b:0;s:13:" * isRequired";b:1;s:15:" * defaultValue";N;s:14:" * constraints";a:0:{}s:14:" * _serviceIds";a:1:{s:16:"typedDataManager";s:18:"typed_data_manager";}s:18:" * _entityStorages";a:0:{}}s:23:" * cacheabilityMetadata";O:35:"Drupal\Core\Cache\CacheableMetadata":3:{s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;}s:15:" * contextValue";N;s:14:" * _serviceIds";a:1:{s:16:"typedDataManager";s:18:"typed_data_manager";}s:18:" * _entityStorages";a:0:{}}}s:11:" * pluginId";s:9:"overrides";s:19:" * pluginDefinition";O:61:"Drupal\layout_builder\SectionStorage\SectionStorageDefinition":6:{s:9:" * weight";i:-20;s:13:" * additional";a:0:{}s:5:" * id";s:9:"overrides";s:8:" * class";s:67:"Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage";s:11:" * provider";s:14:"layout_builder";s:21:" * contextDefinitions";a:2:{s:6:"entity";O:44:"Drupal\Core\Plugin\Context\ContextDefinition":9:{s:11:" * dataType";s:6:"entity";s:8:" * label";N;s:14:" * description";N;s:13:" * isMultiple";b:0;s:13:" * isRequired";b:1;s:15:" * defaultValue";N;s:14:" * constraints";a:1:{s:14:"EntityHasField";s:22:"layout_builder__layout";}s:14:" * _serviceIds";a:1:{s:16:"typedDataManager";s:18:"typed_data_manager";}s:18:" * _entityStorages";a:0:{}}s:9:"view_mode";O:44:"Drupal\Core\Plugin\Context\ContextDefinition":9:{s:11:" * dataType";s:6:"string";s:8:" * label";N;s:14:" * description";N;s:13:" * isMultiple";b:0;s:13:" * isRequired";b:1;s:15:" * defaultValue";N;s:14:" * constraints";a:0:{}s:14:" * _serviceIds";a:1:{s:16:"typedDataManager";s:18:"typed_data_manager";}s:18:" * _entityStorages";a:0:{}}}}s:16:" * configuration";a:0:{}s:19:" * typedDataManager";N;s:20:" * stringTranslation";N;s:14:" * _serviceIds";a:3:{s:17:"entityTypeManager";s:19:"entity_type.manager";s:18:"entityFieldManager";s:20:"entity_field.manager";s:21:"sectionStorageManager";s:45:"plugin.manager.layout_builder.section_storage";}s:18:" * _entityStorages";a:0:{}s:8:"contexts";a:0:{}}}s:5:"owner";i:1;s:7:"updated";i:280342800;}',
  ])
  ->execute();
