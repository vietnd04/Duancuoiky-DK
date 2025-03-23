<script lang="ts" setup>
import { onMounted } from "vue";
import MemberTable from "./components/MemberTable.vue";
import { MemberType, useGetMembers, useGetUsersAndMembers } from "./actions/GetMember";
import { useRouter } from "vue-router";
import { memberStore } from "./store/memberStore";
import { MemberInputType } from "./actions/createMember";

const { getMembers, loading: memberLoading, memberData } = useGetMembers();
const { getUsersAndMembers, loading: usersAndMembersLoading, usersAndMembersData } = useGetUsersAndMembers();

async function showListOfMembers() {
    await getMembers();
    await getUsersAndMembers();
}

const router=useRouter()
function editMember(member:MemberType){
    memberStore.memberInput=member
    memberStore.edit=true
    router.push('/create-members')
}

onMounted(async () => {
    showListOfMembers();
    memberStore.edit=false
    memberStore.memberInput={} as MemberInputType
});
</script>
<template>
    <div class="container">
        <div class="row">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        Members
                        <RouterLink
                            style="float: right"
                            to="/create-members"
                            class="btn btn-primary"
                            >Create Member</RouterLink
                        >
                    </div>
                    <div class="card-body">
                        <MemberTable
                        @editMember="editMember"
                            :loading="memberLoading"
                            @getMember="getMembers"
                            :members="memberData"
                        >
                            <template #pagination>
                                <Bootstrap5Pagination 
                                v-if="memberData?.data"
                                :data="memberData?.data"
                                @pagination-change-page="getMembers"
                                />
                            </template>
                        </MemberTable>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        Users
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="usersAndMembersLoading">
                                    <td colspan="3" class="text-center">Loading users...</td>
                                </tr>
                                <tr v-else-if="!usersAndMembersData?.users?.length">
                                    <td colspan="3" class="text-center">No users found</td>
                                </tr>
                                <tr v-for="user in usersAndMembersData?.users" :key="user.id">
                                    <td>{{ user.id }}</td>
                                    <td>{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
