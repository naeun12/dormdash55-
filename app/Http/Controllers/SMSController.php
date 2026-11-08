<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\userEmail;
use Illuminate\Support\Facades\Mail;

class landingPageController extends Controller
{
    // I-display ang landing page sa website.
    public function landingPage()
    {
        return view('landingpage.landingpage');
    }

    // I-process ang contact form ug ipadala ang email sa user.
    public function sendEmail(Request $request)
    {
        // I-validate ang mga input gikan sa contact form.
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'message' => 'required|string|max:1000',
        ]);

        // Kuhaon ang pangalan ug mensahe nga gisubmit sa user.
        $tenantName = $request->name;
        $messageBody = $request->message;

        // Ipadala ang email gamit ang userEmail Mailable class.
        Mail::to($request->email)->send(new userEmail($tenantName, $messageBody));

        // Ibalik ang user sa previous page uban ang success message.
        return back()->with('success', 'Your message has been sent successfully!');
    }
}

