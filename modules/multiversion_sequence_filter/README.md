# Multiversion Sequence filter

This module improves [replication](http://drupal.org/project/replication) by providing an improved sequence filter index of
the [Multiversion module](http://drupal.org/project/multiversion). The module has been developed for the
 [Contentpool](http://drupal.org/project/contentpool) distribution, but is generally usable.

## Features

 - Improves replication performance when a site has a large history of changes or large number of content items and
   replication filters are used. It does that, by applying replication filters on the database-level. Performance
   improvements are in particular large for the first-time replication.
 - Adds additional, related entities to the replicated entities and ensures those additional entities get replicated
   when an entity matches the filter. It support recursing entity references once, such that media entity + file entity
   references can be covered. 
 - TODO: Remove replicated entities from replication targets once the replication filter stops matching.  
   

## Usage
- The module requires a filter plugin which supports the module. Please refer to the [contentpool filter plugin](https://github.com/drunomics/contentpool-replication/blob/issue-3011802/src/Plugin/ReplicationFilter/ContentpoolFilter.php) 
  for an example implementation.
- The module and filter plugin must be installed when a site is installed. When the modules are installed on sites with
  existing content, all entities must be re-saved in order to update sequence index.
- If replication is performed uni-directional, it's sufficient to install the module on the replication source.
   
## Implementation
 - The index is written to dedicated database table which are optimized for the use case. Replication filter plugins
   may provide filter values for a given entity, which are then stored to a dedicated index table. That way, application
   of filter values can happen at the database level. 
 - As an optimization, the module only keeps one sequence per entity, i.e. the last one. So only the latest entity
   revision will be replicated.
 - In order to improve performance when calculated the replication changes, entity revisions are stored in-side the
   index items.
   
## Further information
 * See https://www.drupal.org/project/contentpool/issues/3005163 and https://www.drupal.org/project/contentpool/issues/3011802#comment-12922057
   for background information on the architecture of the module.
 * See \Drupal\multiversion_sequence_filter\FilteredSequenceIndex::getAdditionalEntries() for the additionally replicated
   entities. 

## Credits

* initial development by fago // Wolfgang Ziegler, drunomics GmbH <hello@drunomics.com>
