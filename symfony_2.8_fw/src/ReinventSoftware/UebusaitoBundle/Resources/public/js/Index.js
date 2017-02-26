/* global utility, loader, flashBag, language, search, authentication, registration, recoverPassword, controlPanelProfile, controlPanelPayment, controlPanelPage, controlPanelUser, controlPanelModule, controlPanelRole, controlPanelSetting */

$(document).ready(function() {
    utility.linkPreventDefault();
    
    utility.mobileCheck(true);
    
    utility.watch("#flashBag", flashBag.sessionActivity);
    
    utility.bootstrapMenuFix("#menu_root_navbar");
    
    utility.bootstrapMenuActiveFix(
        [
            "#menu_root_navbar",
            "#menu_registration",
            "#menu_control_panel"
        ]
    );
    
    loader.create("font");
    
    language.init();
    search.init();
    authentication.init();
    registration.init();
    recoverPassword.init();
    controlPanelProfile.init();
    controlPanelPayment.init();
    controlPanelPage.init();
    controlPanelUser.init();
    controlPanelModule.init();
    controlPanelRole.init();
    controlPanelSetting.init();
});