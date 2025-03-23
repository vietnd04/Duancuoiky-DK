<script lang="ts" setup>
import { onMounted, ref } from "vue";
import { useGetPinnedProject } from "./actions/GetPinnedProject";
import { useGetTotalProject } from "./actions/countProject";
import { useGetChartData } from "./actions/getChartData";

const { project, getPinnedProject } = useGetPinnedProject();
const { countProject, getTotalProject } = useGetTotalProject();

const { chartData, getChartData } = useGetChartData();
onMounted(async () => {
    await getPinnedProject();
    getChartData(project.value.id);
    getTotalProject();
});
</script>
<template>
    <div class="row">
        <h2>Dashbaord</h2>
        <br />
        <br />
        <br />
        <div class="row">
            <div class="col-md-8">
                <h3 style="color: rgb(118, 119, 120)">
                    Project :{{ project?.name }}
                </h3>
            </div>
        </div>
        <br /><br />
        <div class="row">
            <div class="col-md-4 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <b>Total Projects</b>
                    </div>
                    <div class="card-body">
                        <br />
                        <br />

                        <h2 align="center">{{ countProject?.count }}</h2>
                        <br />
                        <br />
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card">
                    <div class="card-header"><b>Tasks Đang Làm</b></div>
                    <div class="card-body">
                        <div class="text-center py-5">
                            <h2 v-if="chartData.tasks && chartData.tasks.length > 0">{{ chartData.tasks[0] }}</h2>
                            <h2 v-else>0</h2>
                            <p class="text-muted">Task đang xử lý</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <b>Tasks Đã Hoàn Thành</b>
                    </div>

                    <div class="card-body">
                        <div class="text-center py-5">
                            <h2 v-if="chartData.tasks && chartData.tasks.length > 1">{{ chartData.tasks[1] }}</h2>
                            <h2 v-else>0</h2>
                            <p class="text-muted">Task đã hoàn thành</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
