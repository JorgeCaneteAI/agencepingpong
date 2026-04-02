//
//  ContentView.swift
//  blog villa plaisance
//
//  Created by Jorge Canete on 04/06/2023.
//

import SwiftUI

struct ContentView: View {
    @Binding var document: blog_villa_plaisanceDocument

    var body: some View {
        TextEditor(text: $document.text)
    }
}

struct ContentView_Previews: PreviewProvider {
    static var previews: some View {
        ContentView(document: .constant(blog_villa_plaisanceDocument()))
    }
}
