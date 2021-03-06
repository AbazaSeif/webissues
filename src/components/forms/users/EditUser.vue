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
  <BaseForm v-bind:title="title" with-buttons v-on:ok="submit" v-on:cancel="cancel">
    <Prompt v-if="mode == 'edit'" path="prompt.EditUser"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'account'" path="prompt.EditAccount"></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddUser"></Prompt>
    <FormInput v-if="isAdministrator" ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormInput v-if="isAdministrator" ref="login" id="login" v-bind:label="$t( 'label.Login' )" v-bind="$field( 'login' )" v-model="login"/>
    <FormCheckbox v-if="mode == 'add' && canResetPassword" v-bind:label="$t( 'text.SendInvitationEmail' )" v-model="sendInvitationEmail"/>
    <FormInput v-if="mode == 'add'" ref="password" id="password" type="password" v-bind:label="$t( 'label.Password' )"
               v-bind:disabled="sendInvitationEmail" v-bind="$field( 'password' )" v-model="password"/>
    <FormInput v-if="mode == 'add'" ref="confirmPassword" id="confirmPassword" type="password" v-bind:label="$t( 'label.ConfirmPassword' )"
               v-bind:disabled="sendInvitationEmail" v-bind="$field( 'confirmPassword' )" v-model="confirmPassword"/>
    <FormCheckbox v-if="mode == 'add'" v-bind:label="$t( 'text.UserMustChangePassword' )" v-bind:disabled="sendInvitationEmail" v-model="mustChangePassword"/>
    <FormInput ref="email" id="email" v-bind:label="$t( 'label.EmailAddress' )" v-bind="$field( 'email' )" v-model="email"/>
    <FormDropdown v-bind:label="$t( 'label.Language' )" v-bind:items="languageItems" v-bind:item-names="languageNames"
                  v-bind:default-name="$t( 'text.DefaultLanguage' )" v-model="language"/>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { MaxLength, ErrorCode, Reason, Access } from '@/constants'
import { makeParseError } from '@/utils/errors'

export default {
  props: {
    mode: String,
    userId: Number,
    initialName: String,
    initialLogin: String,
    initialEmail: String,
    initialLanguage: String
  },

  fields() {
    const isAdministrator = this.$store.state.global.userAccess == Access.AdministratorAccess;

    return {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Name,
        condition: isAdministrator
      },
      login: {
        value: this.initialLogin,
        type: String,
        required: true,
        maxLength: MaxLength.Login,
        condition: isAdministrator
      },
      sendInvitationEmail: {
        type: Boolean
      },
      password: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        condition: () => this.mode == 'add' && !this.sendInvitationEmail
      },
      confirmPassword: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        parse: this.comparePassword,
        condition: () => this.mode == 'add' && !this.sendInvitationEmail
      },
      mustChangePassword: {
        type: Boolean
      },
      email: {
        value: this.initialEmail,
        type: String,
        required: false,
        maxLength: MaxLength.Value,
        parse: this.checkEmailAddress
      },
      language: {
        value: this.initialLanguage,
        type: String,
        required: false
      }
    };
  },

  computed: {
    ...mapState( 'global', [ 'languages' ] ),
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditUser' );
      else if ( this.mode == 'account' )
        return this.$t( 'cmd.EditAccount' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddUser' );
    },
    canResetPassword() {
      return this.$store.state.global.settings.resetPassword;
    },
    languageItems() {
      return this.languages.map( l => l.key );
    },
    languageNames() {
      return this.languages.map( l => l.name );
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( ( this.mode == 'edit' || this.mode == 'account' ) && !this.$fields.modified() ) {
        this.returnToDetails( this.userId );
        return;
      }

      const data = {};
      if ( this.mode == 'edit' || this.mode == 'account' && this.isAdministrator )
        data.userId = this.userId;
      if ( this.isAdministrator ) {
        data.name = this.name;
        data.login = this.login;
      }
      if ( this.sendInvitationEmail ) {
        data.sendInvitationEmail = true;
      } else if ( this.mode == 'add' ) {
        data.password = this.password;
        data.mustChangePassword = this.mustChangePassword;
      }
      data.email = this.email;
      data.language = this.language;

      this.$form.block();

      let url;
      if ( this.mode == 'account' ) {
        if ( this.isAdministrator )
          url = '/users/edit.php';
        else
          url = '/account/edit.php';
      } else {
        url = '/users/' + this.mode + '.php';
      }

      this.$ajax.post( url, data ).then( ( { userId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        if ( changed && userId == this.$store.state.global.userId ) {
          const language = this.language != '' ? this.language : this.$store.state.global.settings.defaultLanguage;
          this.$i18n.setLocale( language ).then( () => {
            this.returnToDetails( userId );
          } );
        } else {
          this.returnToDetails( userId );
        }
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.UserAlreadyExists ) {
          this.$form.unblock();
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.LoginAlreadyExists ) {
          this.$form.unblock();
          this.loginError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.login.focus();
          } );
        } else if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.EmailAlreadyExists ) {
          this.$form.unblock();
          this.emailError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.email.focus();
          } );
        } else {
          this.$form.error( error );
        }
      } );
    },

    cancel() {
      if ( this.mode == 'edit' || this.mode == 'account' )
        this.returnToDetails( this.userId );
      else
        this.$router.push( 'ManageUsers' );
    },

    returnToDetails( userId ) {
      if ( this.mode == 'account' )
        this.$router.push( 'MyAccount' );
      else
        this.$router.push( 'UserDetails', { userId } );
    },

    comparePassword( value ) {
      if ( this.password != '' && this.password != value )
        throw makeParseError( this.$t( 'ErrorCode.' + ErrorCode.PasswordNotMatching ) );
      return value;
    },

    checkEmailAddress( value ) {
      if ( value != '' )
        this.$parser.checkEmailAddress( value );
      else if ( this.sendInvitationEmail )
        throw makeParseError( this.$t( 'error.NoEmailForInvitation' ) );
      return value;
    }
  },

  mounted() {
    if ( this.isAdministrator )
      this.$refs.name.focus();
    else
      this.$refs.email.focus();
  }
}
</script>
