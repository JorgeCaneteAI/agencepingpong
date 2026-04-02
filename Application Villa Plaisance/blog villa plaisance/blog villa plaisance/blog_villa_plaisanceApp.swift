//
//  blog_villa_plaisanceApp.swift
//  blog villa plaisance
//
//  Created by Jorge Canete on 04/06/2023.
//

import SwiftUI

@main
struct blog_villa_plaisanceApp: App {
    var body: some Scene {
        DocumentGroup(newDocument: blog_villa_plaisanceDocument()) { file in
            ContentView(document: file.$document)
        }
    }
}
