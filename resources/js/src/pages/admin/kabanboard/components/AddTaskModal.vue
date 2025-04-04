<script setup lang="ts">
import { useVuelidate } from "@vuelidate/core";
import { required } from "@vuelidate/validators";
import { ref, onMounted } from "vue";
import { taskStore } from "../store/kabanStore";
import { GetMemberType, useGetUsersAndMembers, UserType } from "../../member/actions/GetMember";
import { useSelectMember } from "../actions/selectMember";
import { useCreateTask } from "../actions/CreateTask";
import { showError } from "../../../../helper/toast-notification";
import { myDebounce } from "../../../../helper/util";

defineProps<{
    members: GetMemberType;
}>();

const emit = defineEmits<{
    (e: "closeModal"): void;
    (e: "refreshKabanBoard",): Promise<void>;
    (e: "getMembers",page:number,query:string): Promise<void>;
}>();

const rules = {
    name: { required }, // Matches state.lastName
};
const v$ = useVuelidate(rules, taskStore.taskInput);
const query = ref("");

const { selectMember, unSelectMember, selectedMembers } = useSelectMember();
const { loading, createTask } = useCreateTask();
const { getUsersAndMembers, usersAndMembersData, loading: usersLoading } = useGetUsersAndMembers();

onMounted(async () => {
    await getUsersAndMembers();
});

function selectUser(user: UserType) {
    // Convert user object to member format and select
    const userAsMember = {
        id: user.id,
        name: user.name,
        email: user.email
    };
    selectMember(userAsMember);
}

async function submitTask() {
    const result = await v$.value.$validate();

    if (!result) return;
  
    if(taskStore.taskInput.memberIds.length > 0){
        await createTask();
        taskStore.taskInput.memberIds=[]
        taskStore.taskInput.name=""
        v$.value.$reset()
        emit('refreshKabanBoard')
    }else{
        showError('please select a member or user!')
    }    
}

const searchMember=myDebounce(async function(){
    emit('getMembers',1,query.value)
    await getUsersAndMembers(1, query.value);
},200)
</script>
<template>
    <!-- Modal -->
    <div
        class="modal fade"
        id="taskModal"
        tabindex="-1"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!--  -->
                <form
                    enctype="multipart/form-data"
                    @submit.prevent="submitTask"
                >
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">
                            Add Task
                        </h1>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            @click="emit('closeModal')"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="select-members">
                            <span
                                v-for="member in selectedMembers"
                                @click="unSelectMember(member.id)"
                                :key="member.id"
                            >
                                {{ member.email }} <b>x</b>
                            </span>
                        </div>

                        <div class="row">
                            <div class="row">
                                <!-- {{ taskStore.taskInput }} -->
                                <div class="col-md-9">
                                    <Error :errors="v$.name.$errors">
                                        <BaseInput
                                            placeholder="Task name"
                                            v-model="taskStore.taskInput.name"
                                        />
                                    </Error>
                                </div>

                                <div class="col-md-3">
                                    <BaseBtn
                                        :loading="loading"
                                        label="Add Task"
                                    />
                                </div>
                            </div>
                            <br />
                            <br />
                            <br />

                            <div class="form-group">
                                <!-- @keyup="searchMember" -->
                                <BaseInput
                                    type="text"
                                    v-model="query"
                                    @keydown="searchMember"
                                    placeholder="Search members or users..."
                                />
                            </div>

                            <br />
                            <br />
                        </div>
                        <br />
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Members</h5>
                                <table class="table table-hover table-sm">
                                    <thead style="font-weight: bold">
                                        <tr>
                                            <td>ID</td>
                                            <td>Name</td>
                                            <td>Select</td>
                                        </tr>
                                    </thead>
                                    <tr
                                        v-for="member in members?.data?.data"
                                        :key="member.id"
                                    >
                                        <td># {{ member.id }}</td>
                                        <td>{{ member.name }}</td>
                                        <td>
                                            <button
                                                @click="selectMember(member)"
                                                type="button"
                                                class="btn btn-light"
                                                style="
                                                    border-radius: 10px;
                                                    background-color: aliceblue;
                                                "
                                            >
                                                <b>+</b> Add
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Users</h5>
                                <table class="table table-hover table-sm">
                                    <thead style="font-weight: bold">
                                        <tr>
                                            <td>ID</td>
                                            <td>Email</td>
                                            <td>Select</td>
                                        </tr>
                                    </thead>
                                    <tr v-if="usersLoading">
                                        <td colspan="3" class="text-center">Loading users...</td>
                                    </tr>
                                    <tr v-else-if="!usersAndMembersData?.users?.length">
                                        <td colspan="3" class="text-center">No users found</td>
                                    </tr>
                                    <tr
                                        v-for="user in usersAndMembersData?.users"
                                        :key="user.id"
                                    >
                                        <td># {{ user.id }}</td>
                                        <td>{{ user.email }}</td>
                                        <td>
                                            <button
                                                @click="selectUser(user)"
                                                type="button"
                                                class="btn btn-light"
                                                style="
                                                    border-radius: 10px;
                                                    background-color: aliceblue;
                                                "
                                            >
                                                <b>+</b> Add
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<style scoped>
.select-members span {
    padding: 5px;
    border-radius: 4px;
    border: 1px solid gray;
    cursor: pointer;
    margin: 2px;
}
.select-members span b {
    color: red;
}
</style>
