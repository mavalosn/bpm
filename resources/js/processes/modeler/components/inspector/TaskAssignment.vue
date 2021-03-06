<template>
    <div>
        <div class="form-group">
            <label>Task Assignment</label>
            <select ref="assignmentsDropDownList"
                    class="form-control"
                    :value="assignmentGetter"
                    @input="assignmentSetter">
                <option value=""></option>
                <option value="requestor">To requestor</option>
                <option value="user">To user</option>
                <option value="group">To group</option>
            </select>
        </div>

        <div class="form-group" v-if="showAssignOneUser">
            <label>Assigned User</label>
            <div v-if="loadingUsers">Loading...</div>
            <select v-else class="form-control" :value="assignedUserGetter"
                    @input="assignedUserSetter">
                <option></option>
                <option v-for="(row, index) in users" v-bind:value="row.id" :selected="row.id == assignedUserGetter">
                    {{row.fullname}}
                </option>
            </select>
        </div>
        
        <div class="form-group" v-if="showAssignGroup">
            <label>Assigned Group</label>
            <div v-if="loadingGroups">Loading...</div>
            <select v-else class="form-control" :value="assignedGroupGetter"
                    @input="assignedGroupSetter">
                <option></option>
                <option v-for="(row, index) in groups" v-bind:value="row.id" :selected="row.id == assignedGroupGetter">
                    {{row.name}}
                </option>
            </select>
        </div>

        <form-checkbox label="Allow Reassignment" :checked="allowReassignmentGetter" @change="allowReassignmentSetter"></form-checkbox>
    </div>
</template>

<script>
    export default {
        props: ["value", "label", "helper", "property"],
        data() {
            return {
                users: [],
                groups: [],
                loadingUsers: true,
                loadingGroups: true,
            };
        },
        computed: {
            /**
             * Get the value of the edited property
             */
            allowReassignmentGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'allowReassignment');
                return value;
            },
            /**
             * Get owner process.
             *
             * @returns {object}
             */
            process() {
                return this.$parent.$parent.$parent.process;
            },
            assignedUserGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'assignedUsers');
                return value;
            },
            assignedGroupGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'assignedGroups');
                return value;
            },
            assignmentGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'assignment');
                return value;
            },
            node() {
                return this.$parent.$parent.highlightedNode.definition;
            },
            showAssignOneUser() {
                return this.assignmentGetter === 'user';
            },
            showAssignGroup() {
                return this.assignmentGetter === 'group';
            },
        },
        methods: {
            /**
             * Update allowReassignment property
             */
            allowReassignmentSetter(value) {
                this.$set(this.node, 'allowReassignment', value);
                this.$emit('input', this.value);
            },
            /**
             * Load the list of assigned users
             */
            loadUsersAndGroups() {
                this.loadingUsers = true;
                this.users = []
                ProcessMaker.apiClient
                    .get("/users")
                    .then(response => {
                        this.users.push(...response.data.data);
                        this.loadingUsers = false;
                    });
                
                this.loadingGroups = true;
                this.groups = []
                ProcessMaker.apiClient
                    .get("/groups")
                    .then(response => {
                        this.groups.push(...response.data.data);
                        this.loadingGroups = false;
                    });
            },
            /**
             * Update the event of the editer property
             */
            assignedUserSetter(event) {
                this.$set(this.node, 'assignedUsers', event.target.value);
                this.$emit('input', this.value);
            },
            assignedGroupSetter(event) {
                this.$set(this.node, 'assignedGroups', event.target.value);
                this.$emit('input', this.value);
            },
            /**
             * Update the event of the editer property
             */
            assignmentSetter(event) {
                this.$set(this.node, 'assignment', event.target.value);
                this.$emit('input', this.value);
            },
        },
        mounted() {
            this.loadUsersAndGroups();
        }
    };
</script>

<style lang="scss" scoped>
    .list-users-groups {
        width: 100%;
        height: 24em;
        overflow-y: auto;
        font-size: 0.75rem;
        background: white;
    }
    .list-users-groups.small {
        height: 8em;
    }
</style>
