
import PageLayout from '@/components/shared/PageLayout.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import PageFooter from '@/components/shared/PageFooter.vue';
import PageSection from '@/components/shared/PageSection.vue';
import PageTitle from '@/components/shared/PageTitle.vue';
import Btn from '@/components/shared/Btn.vue';
import Block from '@/components/shared/Block.vue';
import Lorem from '@/components/shared/Lorem.vue';


export const ProjectPlugin = {
    install: (app, options) => {


        app.component("page-layout", PageLayout);
        app.component("page-header", PageHeader);
        app.component("page-title", PageTitle);
        app.component("page-footer", PageFooter);
        app.component("page-section", PageSection);
        app.component("block", Block);
        app.component("btn", Btn);
        app.component("lorem", Lorem);
/*
        app.component("btn-flat", BtnFlat);
        app.component("btn-ok", BtnOk);
        app.component("btn-cancel", BtnCancel);
        app.component("btn-profile", BtnProfile);
        app.component("btn-edit", BtnEdit);
        app.component("btn-back", BtnBack);
*/
    }
}

