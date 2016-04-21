AbstractController still extends Symfony base controller.

AbstractController2 does not.  
  redirect
  redirectToRoute

PageTTemplate was only being used by Admin.  Replaces with AdminView(2).

AbstractView2
  setProject
    appBaseTemplate
    appPageTTemplate
    abstractView
    abstractView2 
      No easy way of teling if $this->project is being used
      Remove the call from the service
        WelcomeView
          no project usage
        RegisterTemplateEmail
          Currently extends from AbstractView2 which it should not
          No project stuff
        RegisterView
          No project stuff
        PasswordResetRequestView
          No project stuff
        PasswordResetResponseView
          Used project

GameReportUpdateView
  Extends AbstractTemplate
  Change to AbstractView2 ***

Changes
- Moved Action\Admin to Action\App\Admin
- Replace AdminPageTemplate with AdminView
- Removed setProject from AbstractView2
- Removed PageTTemplate class
- GameReportUpdateView now extends AbstractView2
    Needs fix for return to schedule url
