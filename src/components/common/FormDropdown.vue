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
  <FormGroup v-bind:label="label" v-bind:required="required" v-bind:error="error">
    <div class="dropdown-select">
      <DropdownButton ref="dropdown" v-bind:text="text" v-bind:disabled="disabled" v-on:open="open">
        <div ref="scroll" class="dropdown-menu-scroll">
          <li v-if="defaultName != null" v-bind:class="{ active: value == null || value == '' }">
            <HyperLink v-on:click="select( null )">{{ defaultName }}</HyperLink>
          </li>
          <li v-if="defaultName != null" role="separator" class="divider"></li>
          <li v-for="( item, index ) in items" v-bind:key="item" v-bind:class="{ active: item == value }">
            <HyperLink v-on:click="select( item )">{{ itemNames[ index ] }}</HyperLink>
          </li>
        </div>
      </DropdownButton>
    </div>
  </FormGroup>
</template>

<script>
export default {
  props: {
    value: [ String, Number ],
    label: String,
    required: Boolean,
    error: String,
    items: Array,
    itemNames: Array,
    defaultName: String,
    disabled: Boolean
  },
  computed: {
    text() {
      if ( this.defaultName != null && ( this.value == null || this.value == '' ) )
        return this.defaultName;
      return this.itemNames[ this.items.indexOf( this.value ) ];
    }
  },
  methods: {
    focus() {
      this.$refs.dropdown.focus();
    },
    select( item ) {
      this.$emit( 'input', item );
    },
    open() {
      this.$nextTick( this.scrollItemToView );
    },
    scrollItemToView() {
      for ( let i = 0; i < this.$refs.scroll.children.length; i++ ) {
        const item = this.$refs.scroll.children[ i ];
        if ( item.className == 'active' ) {
          if ( this.$refs.scroll.scrollTop > item.offsetTop - 5 )
            this.$refs.scroll.scrollTop = item.offsetTop - 5;
          else if ( this.$refs.scroll.scrollTop + this.$refs.scroll.clientHeight < item.offsetTop - 5 + item.clientHeight )
            this.$refs.scroll.scrollTop = item.offsetTop - 5 + item.clientHeight - this.$refs.scroll.clientHeight;
          break;
        }
      }
    }
  }
}
</script>
