File Sync and Clone
===================



File cloning across multiple instances of a channel is a very hard problem, due to the nature of PHP memory allocation. This needs to be handled dramatically differently than cloning or syncing of other information. (Processing one large video file or 40-50 photos could exhaust memory). Therefore we can't easily just dump all the data to a dump file and sequentially process it. Loading the dump file itself is likely to exhaust memory.

There are also two primary operations we are considering. The first is the hardest - saving and then importing all your channel information into a new channel clone. The second is synchronising file changes as they occur across two or more "active" clones.

For the first cut at this tool we will concentrate on the second case, while trying to maintain some measure of compatibility with the first case so that we can re-use the same tools.

Meta Data
=========


First we need the metadata for the file in order to precisely re-construct its structure on another site. This requires the following information:

'attach' structure (without file contents - which is the default) for the file itself **and** its parent directories so that we can re-create its precise place in the file system, since we do not know if the parent directory has been imported previously or ever. 

'photo' structure for any photo elements which were created as a result of uploading this file into the system. This typically contains several different 'scales' or thumbnail images, some of which may be cropped for profile photo use or cover photo use. We need to retain the cropping information which is not present in the metadata, but only in the stored data. The actual thumbnail image data may or may not be included in the metadata. A cover photo of large scale (scale #7) could potentially cause memory issues. Not as bad as a 100M video, but if you have several of these they could add up.  

'item' entries which are linked to this file. These can be file share activities, the "parent item" linked to photos, and any attached conversation items (photo likes, comments, etc.) 

All of these items will require URL replacement and re-signing of the item as they are relocated to another site.


File Data
=========

Then we have the actual file data we need to reconstruct the file. This needs to be stored separately from the meta-data to avoid memory exhaustion when processing. The actual file data can be used to reconstruct the attach structure and the first four photo scales. If this is a photo, we need access to the "#4 scale" (profile photo) and the #7 scale (cover photo) as they were originally cropped. All other thumbnails can be generated from these. 



File Sync
=========


We will consider this operation first because it is probably the most straightforward to implement. When a photo is added to or removed or changed from the source system, we will send a clone sync packet to all known clones containing the metadata - but **no file data** . We can only send one sync packet per file operation that needs to be synced. 

The receiving end will create and perform URL translation on all the metadata structures and store them. Then it will need to fetch the actual data. Assuming CURL supports streaming, an authenticated request is sent to the original site and the original file is requested and streamed directly to disk (bypassing all processing). If photo scale #4 or scale #7 is required, these are requested and stored into their respective structures. We're assuming in this case that the cover photo large scale will not exhaust memory. If CURL cannot be made to support streaming, request packets need to be queued and sent to the origination site to obtain "chunks" of the file and re-assembled once all chunks have been retrieved.

The authenticated request depends on the mechanism. For CURL streaming, some signed secret with a timestamp will probably need to be generated and posted to the file origination site. Then the data can be retrieved with minimal internal processing and dumped directly to disk using stdio buffering. In the case of a zot request, the zot request packet will be validated, however scheduling chunk batches and re-assembling them could be tricky.


File Backup/Restore
===================

This is much more complicated as we do not have an authenticate web server to request data from. The metadata can be mostly the same, but we need some form of signalling that we will not be fetching the file via the web. This will likely require a client side process to parse each metadata file and locate a file on disk which it is associated with. Then the data would need to be streamed to the destination server with a special endpoint designed for this task. A java app might be the best option here to retain platform neutrality.

Another option would be to use WebDAV for this step. The metadata files would be uploaded first, and then the data files. If a data file corresponded to an existing metadata file, the metadata would be processed; the file stored appropriately, and the metadata file then removed. In this case, photos of scales 4 and 7 would need to be provided in the metadata.  
 

Optionally, this step could also be performed with a filesystem local to the destination server. This would be the highest performance, and a suite of shell-based tools (in the case of Linux) could perform the "client-side" of the task.

The complexity of this task mandates careful planning into how the data is organised and stored and if necessary backed up remotely or transmitted for backup by the source website.


Backward Compatibility
======================

There are some obvious issues with making data available for backup or cloning which existed on the system prior to the existence of restore/sync tools. To keep the tools themselves relatively uncomplicated (to the extent possible given the constraints) backward compatibility may have to be preformed by dedicated plugin or addon.        