<!--
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2017 WebIssues Team
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
  <BaseForm v-bind:title="details.name" size="large" auto-close>

    <template slot="header">
      <button v-if="isAuthenticated" type="button" class="btn btn-primary" v-on:click="editIssue"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</button>
      <DropdownButton v-if="isAuthenticated" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="cloneIssue"><span class="fa fa-clone" aria-hidden="true"></span> {{ $t( 'cmd.CloneIssue' ) }}</HyperLink></li>
        <li v-if="canMoveDelete"><HyperLink v-on:click="moveIssue"><span class="fa fa-exchange" aria-hidden="true"></span> {{ $t( 'cmd.MoveIssue' ) }}</HyperLink></li>
        <li v-if="canMoveDelete"><HyperLink v-on:click="deleteIssue"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.DeleteIssue' ) }}</HyperLink></li>
        <li role="separator" class="divider"></li>
        <li v-if="unread"><HyperLink v-on:click="setUnread( false )"><span class="fa fa-check-circle-o" aria-hidden="true"></span> {{ $t( 'cmd.MarkAsRead' ) }}</HyperLink></li>
        <li v-if="!unread"><HyperLink v-on:click="setUnread( true )"><span class="fa fa-check-circle" aria-hidden="true"></span> {{ $t( 'cmd.MarkAsUnread' ) }}</HyperLink></li>
        <li role="separator" class="divider"></li>
        <li><HyperLink><span class="fa fa-envelope" aria-hidden="true"></span> {{ $t( 'cmd.Subscribe' ) }}</HyperLink></li>
      </DropdownButton>
    </template>

    <div class="row">
      <div class="col-sm-4 col-sm-push-8">

        <div class="issue-details">
          <div class="issue-header">{{ $t( 'title.Properties' ) }}</div>
          <div class="issue-details-title">{{ $t( 'title.ID' ) }}</div>
          <div class="issue-details-value">#{{ details.id }}</div>
          <div class="issue-details-title">{{ $t( 'title.Type' ) }}</div>
          <div class="issue-details-value">{{ typeName }}</div>
          <div class="issue-details-title">{{ $t( 'title.Location' ) }}</div>
          <div class="issue-details-value">{{ projectName }} &mdash; {{ folderName }}</div>
          <div class="issue-details-title">{{ $t( 'title.Created' ) }}</div>
          <div class="issue-details-value">{{ createdDate }} &mdash; {{ createdByName }}</div>
          <div class="issue-details-title">{{ $t( 'title.LastModified' ) }}</div>
          <div class="issue-details-value">{{ modifiedDate }} &mdash; {{ modifiedByName }}</div>
        </div>

        <div v-if="filteredAttributes.length > 0" class="issue-details">
          <div class="issue-header">{{ $t( 'title.Attributes' ) }}</div>
          <template v-for="attribute in filteredAttributes">
            <div v-bind:key="'t' + attribute.id" class="issue-details-title">{{ getAttributeName( attribute.id ) }}</div>
            <div v-bind:key="'v' + attribute.id" class="issue-details-value" v-html="convertAttributeValue( attribute.value, attribute.id, { multiLine: true } )"></div>
          </template>
        </div>

      </div>
      <div class="col-sm-8 col-sm-pull-4">

        <FormSection v-bind:title="$t( 'title.Description' )">
          <DropdownButton v-if="isAuthenticated && description != null" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.Menu' )">
            <li><HyperLink v-on:click="replyDescription"><span class="fa fa-reply" aria-hidden="true"></span> {{ $t( 'cmd.Reply' ) }}</HyperLink></li>
            <li v-if="canEditDescription"><HyperLink v-on:click="editDescription"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</HyperLink></li>
            <li v-if="canEditDescription"><HyperLink v-on:click="deleteDescription"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.Delete' ) }}</HyperLink></li>
          </DropdownButton>
          <button v-else-if="canEditDescription" type="button" class="btn btn-default" v-bind:title="$t( 'cmd.AddDescription' )" v-on:click="addDescription">
            <span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
          </button>
        </FormSection>

        <div v-if="description != null" class="description-panel">
          <div class="formatted-text" v-hljs="description.text"></div>
          <div v-if="isDescriptionModified" class="last-edited">
            <span class="fa fa-pencil" aria-hidden="true"></span> {{ descriptionModifiedDate }} &mdash; {{ descriptionModifiedByName }}
          </div>
        </div>
        <div v-else class="alert alert-info">
          {{ $t( 'info.NoDescription' ) }}
        </div>

        <FormSection v-bind:title="getFilterText( filter )">
          <button v-if="isAuthenticated" type="button" class="btn btn-success" v-bind:title="$t( 'cmd.AddComment' )" v-on:click="addComment">
            <span class="fa fa-comment" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
          </button>
          <button v-if="isAuthenticated" type="button" class="btn btn-default" v-bind:title="$t( 'cmd.AttachFile' )" v-on:click="addFile">
            <span class="fa fa-paperclip" aria-hidden="true"></span> <span class="hidden-xs auto-tooltip">{{ $t( 'cmd.Attach' ) }}</span>
          </button>
          <DropdownButton fa-class="fa-cog" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.Filter' )">
            <li v-for="item in allFilters" v-bind:key="item" v-bind:class="{ active: filter == item }">
              <HyperLink v-on:click="setFilter( item )">{{ getFilterText( item ) }}</HyperLink>
            </li>
          </DropdownButton>
        </FormSection>

        <div class="issue-history">
          <div v-for="item in processedHistory" v-bind:key="item.id" class="issue-history-item" v-bind:id="'item' + item.id">

            <div v-if="isCommentAdded( item ) || isFileAdded( item )" class="issue-group">
              <div class="issue-element issue-element-wide">
                <div class="issue-history-title">{{ formatStamp( item.createdDate ) }} &mdash; {{ getUserName( item.createdBy ) }}</div>
              </div>
              <div class="issue-element">
                <a class="issue-history-id" v-bind:href="'#/items/' + item.id">#{{ item.id }}</a>
                <DropdownButton v-if="canReply( item ) || canEditItem( item )" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.Menu' )">
                  <li v-if="canReply( item )"><HyperLink v-on:click="replyComment( item )"><span class="fa fa-reply" aria-hidden="true"></span> {{ $t( 'cmd.Reply' ) }}</HyperLink></li>
                  <li v-if="canEditItem( item )"><HyperLink v-on:click="editItem( item )"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</HyperLink></li>
                  <li v-if="canEditItem( item )"><HyperLink v-on:click="deleteItem( item )"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.Delete' ) }}</HyperLink></li>
                </DropdownButton>
              </div>
            </div>
            <div v-else class="issue-history-title">{{ formatStamp( item.createdDate ) }} &mdash; {{ getUserName( item.createdBy ) }}</div>

            <div v-if="isCommentAdded( item )" class="issue-comment">
              <div class="formatted-text" v-hljs="item.text"></div>
              <div v-if="isItemModified( item )" class="last-edited">
                <span class="fa fa-pencil" aria-hidden="true"></span> {{ formatStamp( item.modifiedDate ) }} &mdash; {{ getUserName( item.modifiedBy ) }}
              </div>
            </div>
            <div v-else-if="isFileAdded( item )" class="issue-attachment">
              <span class="fa fa-paperclip" aria-hidden="true"></span>
              <a v-if="isWeb" v-bind:href="getFileURL( item.id )" target="_blank">{{ item.name }}</a>
              <HyperLink v-else v-on:click="downloadFile( item )">{{ item.name }}</HyperLink>
              ({{ formatFileSize( item.size ) }})
              <span v-if="item.description" v-html="'&mdash; ' + item.description"></span>
              <div v-if="isItemModified( item )" class="last-edited">
                <span class="fa fa-pencil" aria-hidden="true"></span> {{ formatStamp( item.modifiedDate ) }} &mdash; {{ getUserName( item.modifiedBy ) }}
              </div>
            </div>
            <ul v-else-if="isChangeList( item )" class="issue-history-list">
              <li v-for="change in item.changes" v-bind:key="change.id" v-html="formatChange( change )"></li>
            </ul>
            <ul v-else-if="isIssueMoved( item )" class="issue-history-list">
              <li v-html="formatIssueMoved( item )"></li>
            </ul>

          </div>
          <div v-if="processedHistory.length == 0" class="alert alert-info">
            {{ getNoItemsText( filter ) }}
          </div>
        </div>
      </div>
    </div>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Access, Change, History } from '@/constants'

export default {
  computed: {
    ...mapState( 'global', [ 'baseURL', 'userId', 'projects', 'types', 'users' ] ),
    ...mapGetters( 'global', [ 'isAuthenticated' ] ),
    ...mapState( 'issue', [ 'issueId', 'filter', 'unread', 'details', 'description' ] ),
    ...mapGetters( 'issue', [ 'filteredAttributes', 'isItemInHistory', 'processedHistory' ] ),
    type() {
      return this.types.find( t => t.id == this.details.typeId );
    },
    typeName() {
      if ( this.type != null )
        return this.type.name;
      else
        return this.$t( 'text.UnknownType' );
    },
    project() {
      return this.projects.find( p => p.folders.some( f => f.id == this.details.folderId ) );
    },
    projectName() {
      if ( this.project != null )
        return this.project.name;
      else
        return this.$t( 'text.UnknownProject' );
    },
    folder() {
      if ( this.project != null )
        return this.project.folders.find( f => f.id == this.details.folderId );
      else
        return null;
    },
    folderName() {
      if ( this.folder != null )
        return this.folder.name;
      else
        return this.$t( 'text.UnknownFolder' );
    },
    createdByName() {
      return this.getUserName( this.details.createdBy );
    },
    modifiedByName() {
      return this.getUserName( this.details.modifiedBy );
    },
    createdDate() {
      return this.$formatter.formatStamp( this.details.createdDate );
    },
    modifiedDate() {
      return this.$formatter.formatStamp( this.details.modifiedDate );
    },
    access() {
      if ( this.project != null )
        return this.project.access;
      else
        return Access.NoAccess;
    },
    canMoveDelete() {
      return this.access == Access.AdministratorAccess;
    },
    canEditDescription() {
      return this.access == Access.AdministratorAccess || this.details.createdBy == this.userId;
    },
    isDescriptionModified() {
      return this.description != null && ( ( this.description.modifiedDate - this.details.createdDate ) > 180 || this.description.modifiedBy != this.details.createdBy );
    },
    descriptionModifiedByName() {
      if ( this.description != null )
        return this.getUserName( this.description.modifiedBy );
      else
        return null;
    },
    descriptionModifiedDate() {
      if ( this.description != null )
        return this.$formatter.formatStamp( this.description.modifiedDate );
      else
        return null;
    },
    allFilters() {
      return [
        History.AllHistory,
        History.Comments,
        History.Files,
        History.CommentsAndFiles
      ];
    },
    isWeb() {
      return process.env.TARGET == 'web';
    }
  },

  methods: {
    getAttributeName( attributeId ) {
      const attribute = this.type.attributes.find( a => a.id == attributeId );
      if ( attribute != null )
        return attribute.name;
      else
        return this.$t( 'text.UnknownAttribute' );
    },
    getUserName( userId ) {
      const user = this.users.find( u => u.id == userId );
      if ( user != null )
        return user.name;
      else
        return this.$t( 'text.UnknownUser' );
    },

    formatStamp( stamp ) {
      return this.$formatter.formatStamp( stamp );
    },
    formatFileSize( size ) {
      return this.$formatter.formatFileSize( size );
    },

    convertAttributeValue( value, attributeId, flags = {} ) {
      let attribute = attributeId != null ? this.type.attributes.find( a => a.id == attributeId ) : null;
      if ( attribute == null )
        attribute = { type: 'TEXT' };
      value = this.$formatter.convertAttributeValue( value, attribute, flags );
      return this.$formatter.convertLinks( value );
    },

    canEditItem( item ) {
      return this.access == Access.AdministratorAccess || item.createdBy == this.userId;
    },
    canReply( item ) {
      return this.isAuthenticated && item.type == Change.CommentAdded;
    },

    isCommentAdded( item ) {
      return item.type == Change.CommentAdded;
    },
    isFileAdded( item ) {
      return item.type == Change.FileAdded;
    },
    isChangeList( item ) {
      return item.type == Change.IssueCreated || item.type == Change.IssueRenamed || item.type == Change.ValueChanged;
    },
    isIssueMoved( item ) {
      return item.type == Change.IssueMoved;
    },

    isItemModified( item ) {
      return item.modifiedDate != null && ( ( item.modifiedDate - item.createdDate ) > 180 || item.modifiedBy != item.createdBy );
    },

    getFilterText( filter ) {
      switch( filter ) {
        case History.AllHistory:
          return this.$t( 'text.History' );
        case History.Comments:
          return this.$t( 'text.Comments' );
        case History.Files:
          return this.$t( 'text.Files' );
        case History.CommentsAndFiles:
          return this.$t( 'text.CommentsAndFiles' );
      }
    },
    getNoItemsText( filter ) {
      switch( filter ) {
        case History.Comments:
          return this.$t( 'info.NoComments' );
        case History.Files:
          return this.$t( 'info.NoFiles' );
        case History.CommentsAndFiles:
          return this.$t( 'info.NoCommentsOrFiles' );
      }
    },

    formatChange( change ) {
      let label;
      if ( change.type == Change.ValueChanged )
        label = this.$t( 'label.Attribute', [ this.$formatter.escape( this.getAttributeName( change.attributeId ) ) ] );
      else
        label = this.$t( 'label.Name' );
      label = '<span class="issue-history-label">' + label + '</span>';
      if ( change.type == Change.IssueCreated || change.old == '' )
        return label + ' ' + this.formatValue( change.new, change.attributeId );
      else
        return label + ' ' + this.formatValue( change.old, change.attributeId ) + ' &rarr; ' + this.formatValue( change.new, change.attributeId );
    },
    formatIssueMoved( item ) {
      const label = '<span class="issue-history-label">' + this.$t( 'label.Location' ) + '</span>';
      return label + ' ' + this.formatLocation( item.fromFolderId ) + ' &rarr; ' + this.formatLocation( item.toFolderId );
    },

    formatValue( value, attributeId = null ) {
      if ( value != '' )
        return '<span class="issue-history-value">' + this.convertAttributeValue( value, attributeId ) + '</span>'
      else
        return this.$t( 'text.empty' );
    },
    formatLocation( folderId ) {
      const project = this.projects.find( p => p.folders.some( f => f.id == folderId ) );
      if ( project != null )
        return '<span class="issue-history-value">' + this.$formatter.escape( project.name ) + ' &mdash; ' + this.$formatter.escape( project.folders.find( f => f.id == folderId ).name ) + '</span>';
      else
        return this.$t( 'text.unknown' );
    },

    getFileURL( id ) {
      return this.baseURL + '/client/file.php?id=' + id;
    },

    editIssue() {
      this.$router.push( 'EditIssue', { issueId: this.issueId } );
    },
    cloneIssue() {
      this.$router.push( 'CloneIssue', { issueId: this.issueId } );
    },
    moveIssue() {
      this.$router.push( 'MoveIssue', { issueId: this.issueId } );
    },
    deleteIssue() {
      this.$router.push( 'DeleteIssue', { issueId: this.issueId } );
    },

    addDescription() {
      this.$router.push( 'AddDescription', { issueId: this.issueId } );
    },
    replyDescription() {
      this.$router.push( 'ReplyDescription', { issueId: this.issueId } );
    },
    editDescription() {
      this.$router.push( 'EditDescription', { issueId: this.issueId } );
    },
    deleteDescription() {
      this.$router.push( 'DeleteDescription', { issueId: this.issueId } );
    },

    addComment() {
      this.$router.push( 'AddComment', { issueId: this.issueId } );
    },
    addFile() {
      this.$router.push( 'AddFile', { issueId: this.issueId } );
    },
    replyComment( item ) {
      this.$router.push( 'ReplyComment', { issueId: this.issueId, commentId: item.id } );
    },
    editItem( item ) {
      if ( item.type == Change.CommentAdded )
        this.$router.push( 'EditComment', { issueId: this.issueId, commentId: item.id } );
      else if ( item.type == Change.FileAdded )
        this.$router.push( 'EditFile', { issueId: this.issueId, fileId: item.id } );
    },
    deleteItem( item ) {
      if ( item.type == Change.CommentAdded )
        this.$router.push( 'DeleteComment', { issueId: this.issueId, commentId: item.id } );
      else if ( item.type == Change.FileAdded )
        this.$router.push( 'DeleteFile', { issueId: this.issueId, fileId: item.id } );
    },

    downloadFile( item ) {
      if ( process.env.TARGET == 'electron' )
        this.$router.push( 'ClientDownload', { issueId: this.issueId, fileId: item.id } );
    },

    setUnread( unread ) {
      this.$store.commit( 'issue/setUnread', unread );
      this.update();
    },
    setFilter( filter ) {
      this.$store.commit( 'global/setHistoryFilter', filter );
      this.update();
    },
    update() {
      this.$form.block();
      this.$store.dispatch( 'issue/load' ).then( () => {
        this.$form.unblock();
      } ).catch( error => {
        this.$form.error( error );
      } );
    }
  },

  mounted() {
    const route = this.$router.route;
    if ( route != null && route.name == 'IssueItem' && route.params.issueId == this.issueId )
      this.$form.scrollToAnchor( 'item' + route.params.itemId );
    else
      this.$form.loadPosition( '/issues/' + this.issueId );
  },

  routeChanged( route ) {
    if ( route != null && route.name == 'IssueDetails' && route.params.issueId == this.issueId ) {
      this.$form.loadPosition( '/issues/' + this.issueId );
      return true;
    }

    if ( route != null && route.name == 'Item' ) {
      const itemId = route.params.itemId;

      if ( itemId == this.issueId ) {
        this.$form.resetPosition( '/issues/' + this.issueId );
        this.$router.replace( 'IssueDetails', { issueId: this.issueId } );
        return true;
      }

      if ( this.isItemInHistory( itemId ) ) {
        this.$form.savePosition( '/issues/' + this.issueId );
        this.$router.replace( 'IssueItem', { issueId: this.issueId, itemId } );
        return true;
      }

      this.$form.resetPosition( '/issues/' + itemId );
    }

    this.$form.savePosition( '/issues/' + this.issueId );

    if ( route != null && route.name == 'IssueItem' && route.params.issueId == this.issueId ) {
      this.$form.scrollToAnchor( 'item' + route.params.itemId );
      return true;
    }

    return false;
  }
}
</script>
