<template>
  <div class="data-table">
    <div class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
      >
        <template slot="title" slot-scope="props">
          <b-link v-if="permission.includes('edit-scripts')" @click="onAction('edit-script', props.rowData, props.rowIndex)">{{props.rowData.title}}</b-link>
          <span v-else="permission.includes('edit-scripts')">{{props.rowData.title}}</span>
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onAction('edit-script', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Edit"
                v-if="permission.includes('edit-scripts')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('edit-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Config"
                v-if="permission.includes('edit-scripts')"
              >
                <i class="fas fa-cog fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('remove-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Remove"
                v-if="permission.includes('delete-scripts')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        single="Script"
        plural="Scripts"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";

export default {
  mixins: [datatableMixin],
  props: ["filter", "id", "permission"],
  data() {
    return {
      orderBy: "title",

      sortOrder: [
        {
          field: "title",
          sortField: "title",
          direction: "asc"
        }
      ],

      fields: [
        {
          title: "Name",
          name: "__slot:title",
          field: "title",
          sortField: "title"
        },
        {
          title: "Description",
          name: "description",
          sortField: "description"
        },
        {
          title: "Language",
          name: "language",
          sortField: "language",
          callback: this.formatLanguage
        },
        {
          title: "Modified",
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate"
        },
        {
          title: "Created",
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate"
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },

  methods: {
    goToEdit(data) {
      window.location = "/processes/scripts/" + data + "/edit";
    },
    onAction(action, data, index) {
      switch (action) {
        case "edit-script":
          window.location.href = "/processes/scripts/" + data.id + "/builder";
          break;
        case "edit-item":
          this.goToEdit(data.id);
          break;
        case "remove-item":
          ProcessMaker.confirmModal(
            "Caution!",
            "<b>Are you sure to delete the Script </b>" + data.title + "?",
            "",
            () => {
              this.$emit("delete", data);
            }
          );
          break;
          break;
      }
    },
    formatLanguage(language) {
      return language.toUpperCase();
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "scripts" +
            "?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=user"
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    }
  },

  computed: {}
};
</script>

<style lang="scss" scoped>
/deep/ th#_total_users {
  width: 150px;
  text-align: center;
}

/deep/ th#_description {
  width: 250px;
}
</style>
